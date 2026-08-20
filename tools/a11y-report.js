/**
 * Turn a Playwright run into the accessibility comment posted on a pull request.
 *
 * Reads the JSON report, pulls the structured axe data each test attached (see
 * `tests/a11y/axe.js`), and writes Markdown to stdout.
 *
 *     node tools/a11y-report.js playwright-report/results.json > comment.md
 *
 * **Grouped by finding, not by test.** The raw run is misleading in a specific way: the
 * header, drawer and footer are on every page, so one bad grey in a template part fails
 * thirty-nine tests. A per-test list reads as thirty-nine problems and buries the count that
 * matters, which is one. So identical (rule, selector, colors) findings collapse into a
 * single row carrying the number of pages it appears on.
 *
 * The audience is the person who has to fix it, and for contrast that is usually a designer
 * rather than a developer. Hence the measured ratio, the two hex values and the required
 * threshold in the table itself — enough to answer "what should this be instead?" without
 * cloning the repository or running anything.
 *
 * Exit code is always 0. The suite's own result is what fails the build; a reporting script
 * that can fail the build would mean a formatting bug hides a green run.
 */
const fs = require( 'fs' );

/** Cap on rows per finding, so one sitewide rule cannot produce an unreadable comment. */
const MAX_NODES = 8;

/** Cap on distinct findings listed in full. */
const MAX_FINDINGS = 25;

/**
 * A stable identity for a failing element.
 *
 * Neither of the obvious keys works. axe's `target` selector lists attribute predicates in
 * whatever order it walked them, so one `<a>` comes back as `a[target][rel]` on one page and
 * `a[rel][target]` on another. The element's `html` is worse: the drawer's site-title link
 * carries `aria-current="page"` on the front page and not elsewhere, which is the same element
 * in two states, and keying on either split one finding into a "38 pages" row and a "1 page"
 * row sitting next to each other.
 *
 * Tag plus class list is what survives both, and it is also what a fix is written against —
 * nobody styles `[aria-current]` here, they style `.brand-sidebar__title`.
 *
 * @param {Object} node Compact node record.
 * @return {string} Signature.
 */
function signature( node ) {
	const html = node.html || '';
	const tag = ( html.match( /^<([a-z0-9-]+)/i ) || [] )[ 1 ];

	if ( ! tag ) {
		return node.target;
	}

	const classes = ( html.match( /\sclass="([^"]*)"/i ) || [] )[ 1 ] || '';

	return `${ tag }.${ classes
		.split( /\s+/ )
		.filter( Boolean )
		.sort()
		.join( '.' ) }`;
}

/**
 * Walk the report's nested suites and yield every test result.
 *
 * @param {Object} report Parsed Playwright JSON report.
 * @return {Array<Object>} Flat list of { title, project, status, attachments }.
 */
function flattenResults( report ) {
	const out = [];

	const walk = ( suite ) => {
		( suite.suites || [] ).forEach( walk );

		( suite.specs || [] ).forEach( ( spec ) => {
			( spec.tests || [] ).forEach( ( test ) => {
				( test.results || [] ).forEach( ( result ) => {
					out.push( {
						title: spec.title,
						project: test.projectName || '',
						status: result.status,
						attachments: result.attachments || [],
					} );
				} );
			} );
		} );
	};

	( report.suites || [] ).forEach( walk );

	return out;
}

/**
 * Read an attachment's JSON, whether the reporter inlined it or wrote it to disk.
 *
 * @param {Object} attachment Playwright attachment record.
 * @return {?Object} Parsed body, or null if it cannot be read.
 */
function readAttachment( attachment ) {
	try {
		if ( attachment.body ) {
			return JSON.parse(
				Buffer.from( attachment.body, 'base64' ).toString( 'utf8' )
			);
		}

		if ( attachment.path && fs.existsSync( attachment.path ) ) {
			return JSON.parse( fs.readFileSync( attachment.path, 'utf8' ) );
		}
	} catch ( e ) {
		return null;
	}

	return null;
}

/**
 * Collapse every node of every violation into one row per distinct problem.
 *
 * @param {Array<Object>} results Flattened test results.
 * @return {{findings: Map, incomplete: Map}} Grouped findings.
 */
function group( results ) {
	const findings = new Map();
	const incomplete = new Map();

	results.forEach( ( result ) => {
		result.attachments
			.filter( ( a ) => 'axe' === a.name )
			.map( readAttachment )
			.filter( Boolean )
			.forEach( ( axe ) => {
				const add = ( target, issues ) => {
					issues.forEach( ( issue ) => {
						issue.nodes.forEach( ( node ) => {
							// See signature() for why neither the selector nor the markup can
							// be the key. Colors stay in it: one element failing at two
							// different ratios is genuinely two problems.
							const key = [
								issue.id,
								signature( node ),
								node.fgColor || '',
								node.bgColor || '',
							].join( '|' );

							if ( ! target.has( key ) ) {
								target.set( key, {
									...issue,
									node,
									pages: new Set(),
									projects: new Set(),
								} );
							}

							target.get( key ).pages.add( axe.page );
							target.get( key ).projects.add( result.project );
						} );
					} );
				};

				add( findings, axe.violations || [] );
				add( incomplete, axe.incomplete || [] );
			} );
	} );

	return { findings, incomplete };
}

/**
 * Format one grouped finding as a table row.
 *
 * @param {Object} finding Grouped finding.
 * @return {string} Markdown row.
 */
function row( finding ) {
	const node = finding.node;

	const measured =
		undefined !== node.contrastRatio
			? `${ node.contrastRatio }:1 → needs ${
					node.expectedContrastRatio || '4.5:1'
			  }`
			: '—';

	const colors =
		node.fgColor && node.bgColor
			? `\`${ node.fgColor }\` on \`${ node.bgColor }\``
			: '—';

	const where = `${ finding.pages.size } page${
		1 === finding.pages.size ? '' : 's'
	}`;
	const at = [ ...finding.projects ].sort().join( ', ' );

	return `| \`${ node.target }\` | ${ colors } | ${ measured } | ${ where } | ${ at } |`;
}

/**
 * Build the whole comment.
 *
 * @param {Object} report Parsed Playwright JSON report.
 * @return {string} Markdown.
 */
function render( report ) {
	const results = flattenResults( report );
	const { findings, incomplete } = group( results );

	const failed = results.filter( ( r ) => 'passed' !== r.status ).length;
	const total = results.length;
	const passed = total - failed;

	const lines = [];

	lines.push( '## Accessibility audit' );
	lines.push( '' );
	lines.push(
		'axe-core, WCAG 2.0/2.1 A + AA, across every template, pattern and block style ' +
			'variant at three viewports.'
	);
	lines.push( '' );

	if ( ! failed ) {
		lines.push(
			`**No violations.** ${ passed }/${ total } checks passed.`
		);
	} else {
		lines.push(
			`**${ findings.size } distinct finding${
				1 === findings.size ? '' : 's'
			}** ` +
				`across ${ failed } failing check${
					1 === failed ? '' : 's'
				} ` +
				`(${ passed }/${ total } passed).`
		);
		lines.push( '' );
		lines.push(
			'Findings are grouped by element and measured colors. Shared chrome — the header, ' +
				'drawer and footer — appears on every page, so one element can account for ' +
				'dozens of failing checks. The count that matters is the number of findings, ' +
				'not the number of red checks.'
		);
	}

	const byRule = new Map();
	findings.forEach( ( finding ) => {
		if ( ! byRule.has( finding.id ) ) {
			byRule.set( finding.id, [] );
		}
		byRule.get( finding.id ).push( finding );
	} );

	let listed = 0;

	/*
	 * Sorted, not left in insertion order.
	 *
	 * This comment is edited in place on every push, so its diff is read as "what changed
	 * about accessibility". Any ordering that can shuffle between runs turns an unchanged set
	 * of findings into a comment that looks like it changed. Playwright's JSON reporter does
	 * appear to emit in file and declaration order rather than completion order — two full
	 * runs of this suite produced byte-identical output despite `fullyParallel` — but that is
	 * an observation about the reporter's internals, not a guarantee it makes, and the cost of
	 * not depending on it is one `sort()`.
	 */
	const rules = [ ...byRule.entries() ].sort( ( a, b ) =>
		a[ 0 ].localeCompare( b[ 0 ] )
	);

	for ( const [ rule, group_ ] of rules ) {
		lines.push( '' );
		lines.push( `### \`${ rule }\` — ${ group_[ 0 ].help }` );
		lines.push( '' );
		lines.push( `<${ group_[ 0 ].helpUrl }>` );
		lines.push( '' );
		lines.push( '| Element | Colors | Contrast | Seen on | Viewports |' );
		lines.push( '| --- | --- | --- | --- | --- |' );

		group_
			// Reach first, then the element as a tiebreak. The tiebreak is not decoration:
			// every finding on this theme today sits at the same page count, so without it the
			// entire table is ordered by nothing but insertion.
			.sort(
				( a, b ) =>
					b.pages.size - a.pages.size ||
					a.node.target.localeCompare( b.node.target )
			)
			.slice( 0, MAX_NODES )
			.forEach( ( finding ) => {
				lines.push( row( finding ) );
				listed++;
			} );

		if ( group_.length > MAX_NODES ) {
			lines.push( '' );
			lines.push(
				`_…and ${
					group_.length - MAX_NODES
				} more element(s) under this rule. ` +
					'See the `playwright-report` artifact for the full list._'
			);
		}

		if ( listed >= MAX_FINDINGS ) {
			lines.push( '' );
			lines.push(
				'_Output truncated. Full detail is in the `playwright-report` artifact._'
			);
			break;
		}
	}

	if ( incomplete.size ) {
		lines.push( '' );
		lines.push( '<details>' );
		lines.push(
			`<summary>${ incomplete.size } item(s) axe could not decide — needs review, does ` +
				'not fail the build</summary>'
		);
		lines.push( '' );
		lines.push(
			'These are cases axe cannot compute automatically, most often text over a ' +
				'background image. They are reported and never failed on: treating them as ' +
				'errors produces a false positive on every hero.'
		);
		lines.push( '' );

		// Sorted for the same reason the tables are, and because an unsorted `slice()` would
		// also change *which* items get shown when the list is truncated.
		[ ...incomplete.values() ]
			.sort(
				( a, b ) =>
					a.id.localeCompare( b.id ) ||
					a.node.target.localeCompare( b.node.target )
			)
			.slice( 0, MAX_NODES )
			.forEach( ( finding ) => {
				lines.push(
					`- \`${ finding.id }\` — \`${ finding.node.target }\``
				);
			} );

		if ( incomplete.size > MAX_NODES ) {
			lines.push( `- _…and ${ incomplete.size - MAX_NODES } more._` );
		}

		lines.push( '' );
		lines.push( '</details>' );
	}

	return lines.join( '\n' );
}

const reportPath = process.argv[ 2 ];

if ( ! reportPath || ! fs.existsSync( reportPath ) ) {
	/*
	 * Exiting 0 here is deliberate: the run has already failed the build, and a second failure
	 * from the reporter would only obscure the first.
	 *
	 * The message says what it means, because the vague version of it was genuinely confusing
	 * once. The suite failed in the *seed* — before Playwright ran at all — and the comment
	 * read "the suite did not produce a report", which sounds like the reporter broke rather
	 * than like nothing was ever audited. Naming the two things that get you here points at
	 * the log line that matters.
	 */
	const run =
		process.env.GITHUB_SERVER_URL &&
		process.env.GITHUB_REPOSITORY &&
		process.env.GITHUB_RUN_ID
			? `${ process.env.GITHUB_SERVER_URL }/${ process.env.GITHUB_REPOSITORY }/actions/runs/${ process.env.GITHUB_RUN_ID }`
			: null;

	process.stdout.write(
		'## Accessibility audit\n\n' +
			'**The audit did not run**, so nothing here says anything about accessibility ' +
			'either way.\n\n' +
			'Playwright produced no results file. That means the job failed before any page ' +
			'was audited — almost always the environment failing to boot, or ' +
			'`tests/a11y/seed.php` refusing to seed. Both print the reason.\n\n' +
			( run ? `See the job log: ${ run }\n` : 'See the job log.\n' )
	);
	process.exit( 0 );
}

process.stdout.write(
	render( JSON.parse( fs.readFileSync( reportPath, 'utf8' ) ) ) + '\n'
);
