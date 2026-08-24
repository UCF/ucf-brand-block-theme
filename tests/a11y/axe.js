/**
 * Shared axe run: load a page, prove it is the page we meant, then audit it.
 *
 * The two halves matter equally. An axe assertion on the wrong page passes — a seeded page
 * that 404s renders the (perfectly accessible) 404 template, and a variant page whose markup
 * went stale renders without the style class and gets audited in its default colors. Both are
 * green runs that checked nothing, which is the failure mode phase 1 already shipped once
 * when `serialize()` quietly returned an empty string. So `auditPage()` refuses to run axe
 * until the response status and the expected selector both check out.
 */
const { expect } = require( '@playwright/test' );
const AxeBuilder = require( '@axe-core/playwright' ).default;

/**
 * WCAG 2.0 and 2.1, levels A and AA — what UCF is held to.
 *
 * `best-practice` is deliberately not in this list. Those rules encode axe's house style
 * (heading order, region landmarks) rather than the standard, and mixing them in means a
 * failing build cannot be read as "this violates WCAG".
 *
 * @type {string[]}
 */
const WCAG_TAGS = [ 'wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa' ];

/**
 * Reduce an axe result to the fields a reader actually needs.
 *
 * Attached to the test rather than only printed, so `tools/a11y-report.js` can build the pull
 * request comment from structured data instead of scraping it back out of an error string.
 * The measured numbers matter most here: "this grey is 2.86:1 and needs 4.5:1" is a question
 * a designer can answer, and "color-contrast failed" is not.
 *
 * @param {Array} issues axe violation or incomplete objects.
 * @return {Array<Object>} Compact records.
 */
function compact( issues ) {
	return issues.map( ( issue ) => ( {
		id: issue.id,
		impact: issue.impact,
		help: issue.help,
		helpUrl: issue.helpUrl,
		nodes: issue.nodes.map( ( node ) => {
			// axe files a rule's measurements under whichever check produced them, so this
			// takes the first check carrying data rather than assuming a position.
			const checks = [
				...( node.any || [] ),
				...( node.all || [] ),
				...( node.none || [] ),
			];
			const data = checks.find( ( check ) => check.data )?.data;

			return {
				target: node.target.join( ' ' ),
				html: node.html.slice( 0, 200 ),
				...( data && 'object' === typeof data
					? {
							contrastRatio: data.contrastRatio,
							expectedContrastRatio: data.expectedContrastRatio,
							fgColor: data.fgColor,
							bgColor: data.bgColor,
							fontSize: data.fontSize,
							fontWeight: data.fontWeight,
					  }
					: {} ),
			};
		} ),
	} ) );
}

/**
 * Render a violation list into something a reviewer can act on without opening the report.
 *
 * @param {Array} violations axe violation objects.
 * @return {string} Readable summary.
 */
function describeViolations( violations ) {
	return violations
		.map( ( violation ) => {
			const targets = violation.nodes
				.slice( 0, 5 )
				.map( ( node ) => `      ${ node.target.join( ' ' ) }` )
				.join( '\n' );

			const more =
				violation.nodes.length > 5
					? `\n      …and ${ violation.nodes.length - 5 } more`
					: '';

			return (
				`  [${ violation.impact }] ${ violation.id }: ${ violation.help }\n` +
				`    ${ violation.helpUrl }\n${ targets }${ more }`
			);
		} )
		.join( '\n\n' );
}

/**
 * Audit one page.
 *
 * @param {import('@playwright/test').Page}     page            Playwright page.
 * @param {import('@playwright/test').TestInfo} testInfo        Current test info, for annotations.
 * @param {Object}                              target          What to load and what to check.
 * @param {string}                              target.path     Path to visit, relative to baseURL.
 * @param {string}                              target.name     Label used in failure messages.
 * @param {string}                              [target.class]  Class that must be in the DOM.
 * @param {number}                              [target.status] Expected HTTP status (default 200).
 * @return {Promise<void>}
 */
async function auditPage( page, testInfo, target ) {
	const expectedStatus = target.status || 200;

	const response = await page.goto( target.path, { waitUntil: 'load' } );

	/*
	 * `page.goto()` resolves to null when the navigation produced no response of its own — a
	 * same-document hash change, mainly. No target in the manifest can do that, so this is not
	 * a case the suite reaches today. It is guarded anyway because the unguarded version fails
	 * as "Cannot read properties of null", which reads like a bug in the helper rather than a
	 * page that did not navigate.
	 */
	const status = response?.status() ?? null;

	expect(
		status,
		`${ target.name }: expected HTTP ${ expectedStatus } at ${ target.path } but got ` +
			`${
				null === status ? 'no response at all' : status
			}. Auditing the wrong page ` +
			'passes for the wrong reason — re-seed before reading anything else in this run.'
	).toBe( expectedStatus );

	/*
	 * The theme's front-end scripts build their markup after load: brand-nav.js injects the
	 * drawer sub-nav and the H2 anchor links, and tabs/view.js swaps a plain stack for a
	 * tablist. Auditing before they have run would test markup no visitor ever sees. One
	 * animation frame past `load` is enough for both, and unlike `networkidle` it waits for
	 * the right thing — these are synchronous DOM writes, not requests.
	 *
	 * `blocks.spec.js` asserts the tab roles actually exist, so if this ever stops being
	 * enough the suite says so rather than quietly auditing the pre-enhancement DOM.
	 */
	await page.evaluate(
		// eslint-disable-next-line no-undef -- Runs in the browser, not in Node.
		() => new Promise( ( resolve ) => requestAnimationFrame( resolve ) )
	);

	if ( target.class ) {
		await expect(
			page.locator( `.${ target.class }` ).first(),
			`${ target.name }: nothing on ${ target.path } carries "${ target.class }". The ` +
				'sample markup in tests/a11y/seed.php has probably gone stale against the ' +
				'block it seeds — axe would have audited the default styling and passed.'
		).toBeAttached();
	}

	/*
	 * A cover's background video is excluded from the audit. axe reaches inside the embed and
	 * audits YouTube's own player — `#movie_player`, the channel-avatar button, the title bar
	 * — reporting three violations in markup this theme neither writes nor can change.
	 *
	 * CONTEXT: dormant as things stand. No template, pattern or fixture ships a cover video
	 * since the home hero's URL was taken out, so nothing matches this today. It is kept
	 * because the failure it prevents is baffling rather than informative: the first author
	 * to set a video on the home page would otherwise turn the suite red with Google's
	 * accessibility bugs and no obvious link to anything in this repository.
	 */
	const results = await new AxeBuilder( { page } )
		.withTags( WCAG_TAGS )
		.exclude( '.wp-block-cover__embed-background' )
		.analyze();

	/*
	 * axe splits "this is wrong" from "a human has to look at this". `incomplete` covers cases
	 * it genuinely cannot decide — text over a background image, mainly — so it is recorded
	 * and not failed on. This is the one policy carried over verbatim from the news theme, and
	 * for the same reason: pa11y conflates the two and turns every hero image into a red build.
	 */
	if ( results.incomplete.length ) {
		const summary = results.incomplete
			.map( ( item ) => `${ item.id } (${ item.nodes.length })` )
			.join( ', ' );

		testInfo.annotations.push( {
			type: 'a11y-needs-review',
			description: `${ target.name }: ${ summary }`,
		} );
	}

	/*
	 * Attached before the assertion below, because a failed assertion ends the test — attach
	 * afterwards and the pull request comment would only ever describe the passing runs.
	 */
	if ( results.violations.length || results.incomplete.length ) {
		await testInfo.attach( 'axe', {
			contentType: 'application/json',
			body: JSON.stringify( {
				page: target.name,
				path: target.path,
				violations: compact( results.violations ),
				incomplete: compact( results.incomplete ),
			} ),
		} );
	}

	expect(
		results.violations,
		`${ results.violations.length } accessibility violation(s) on ${ target.name } ` +
			`(${ target.path }):\n\n${ describeViolations(
				results.violations
			) }\n`
	).toEqual( [] );
}

module.exports = { auditPage, WCAG_TAGS, describeViolations, compact };
