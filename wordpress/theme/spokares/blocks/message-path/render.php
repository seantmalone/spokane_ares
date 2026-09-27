<?php
/**
 * Render: spokares-theme/message-path (How it works opener figure).
 *
 * Option B's message-path scene (_chrome.html #msgpath-scene), drawn inline in
 * the one state the page uses: "relay", ending at the county EOC
 * (.msgpath--to-eoc: no hospital hop), plus the EOC hop-4 marker from
 * how-it-works.html. The state system existed for the cut scrollytelling, so
 * the state is baked in: layers that were hidden in this state are left out and
 * the drawing no longer depends on --mp-* custom properties (only the label
 * size, --mp-fs, which site.css raises on narrow screens).
 * Round-3 review fix: the "147.300 MHz" sub-label under "W7GBU repeater" is
 * removed (the frequency's one home is the settings box below).
 * The "A scenario" pill is drawn in the picture so any crop still says so.
 *
 * @package spokares
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

$spokares_wrapper = get_block_wrapper_attributes( array( 'class' => 'msgpath-panel how-open__fig' ) );
?>
<figure <?php echo $spokares_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by get_block_wrapper_attributes(). ?>>
<svg class="msgpath msgpath--to-eoc" data-state="relay" viewBox="0 0 560 400" role="img" aria-labelledby="mp-t mp-d" xmlns="http://www.w3.org/2000/svg">
<title id="mp-t"><?php echo esc_html__( 'A scenario: one message, four hops', 'spokares' ); ?></title>
<desc id="mp-d"><?php echo esc_html__( 'Power and cell service are down. A red line carries the message from a shelter (1) to net control on the W7GBU repeater (2), then to the county radio room (3) and the EOC (4).', 'spokares' ); ?></desc>
<defs>
<linearGradient id="mp-hill" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#2A3A5C"/><stop offset="1" stop-color="#16223D"/></linearGradient>
<pattern id="mp-rain" width="60" height="48" patternUnits="userSpaceOnUse"><path d="M9 3 L5 11 M31 12 L27 20 M51 4 L47 12 M19 26 L15 34 M43 30 L39 38 M57 38 L53 46 M7 40 L3 48" stroke="#A9B8D6" stroke-opacity=".6" stroke-width="1.1" stroke-linecap="round"/></pattern>
</defs>
<rect x="0" y="0" width="560" height="330" fill="url(#mp-rain)"/>
<path d="M132 330 C 200 318 238 150 280 122 C 322 150 360 318 428 330 Z" fill="url(#mp-hill)" stroke="#C5CEDF" stroke-opacity=".35" stroke-width="1"/>
<g fill="none" stroke="#DCE3EF" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
<path d="M270 124 L280 64 L290 124 M272.4 110 H287.6 M275 94 H285 M277.2 80 H282.8 M272.4 110 L285 94 M275 94 L287.6 110 M275 94 L282.8 80 M277.2 80 L285 94"/>
<path d="M280 64 V46"/>
</g>
<rect x="0" y="330" width="560" height="70" fill="#040916" fill-opacity=".38"/>
<path d="M0 330.5 H560" stroke="#C5CEDF" stroke-opacity=".45" stroke-width="1"/>
<g stroke="#AEBBD4" stroke-width="1.6" stroke-linecap="round" fill="none">
<path d="M112 330 V282 M102 288 H122"/><path d="M244 330 V282 M234 288 H254"/><path d="M336 330 V282 M326 288 H346"/>
</g>
<path d="M0 293 Q56 303 112 288 Q146 304 162 326 M244 288 Q232 302 222 322 M244 288 Q290 303 336 288 L352 293" fill="none" stroke="#56627C" stroke-width="1.4"/>
<g fill="#16233F" stroke="#8190AE" stroke-width="1.3">
<path d="M124 330 V304 L140 290 L156 304 V330 Z"/><path d="M164 330 V308 L178 296 L192 308 V330 Z"/><path d="M294 330 V306 L309 294 L324 306 V330 Z"/>
</g>
<g fill="#2A3857"><rect x="131" y="311" width="7" height="7"/><rect x="143" y="311" width="7" height="7"/><rect x="174" y="314" width="8" height="7"/><rect x="304" y="313" width="10" height="7"/></g>
<g fill="none" stroke="#AEBBD4" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
<path d="M206 330 L214 238 L222 330 M208.4 306 H219.6 M210.2 282 H217.8 M211.8 260 H216.2 M208.4 306 L217.8 282 M210.2 282 L219.6 306 M210.2 282 L216.2 260 M211.8 260 L217.8 282"/>
</g>
<text class="mp-sub" x="214" y="222" text-anchor="middle" style="font-size:calc(var(--mp-fs,17px) * .8)"><?php echo esc_html__( 'Cell service out', 'spokares' ); ?></text>
<rect x="20" y="282" width="84" height="48" fill="#1B2745" stroke="#8190AE" stroke-width="1.4"/>
<rect x="28" y="292" width="16" height="9" fill="#FFC857" fill-opacity=".8"/><rect x="80" y="292" width="16" height="9" fill="#FFC857" fill-opacity=".8"/>
<rect x="54" y="306" width="16" height="24" fill="#0A1328"/>
<path d="M96 282 V252" stroke="#DCE3EF" stroke-width="1.6" stroke-linecap="round"/>
<rect x="17" y="279" width="90" height="54" fill="none" stroke="#FFC857" stroke-width="2"/>
<rect x="352" y="268" width="80" height="62" fill="#1B2745" stroke="#8190AE" stroke-width="1.4"/>
<g fill="#FFC857" fill-opacity=".8"><rect x="361" y="279" width="22" height="9"/><rect x="399" y="279" width="22" height="9"/><rect x="361" y="296" width="22" height="9"/></g>
<rect x="401" y="310" width="16" height="20" fill="#0A1328"/>
<path d="M420 268 V234" stroke="#DCE3EF" stroke-width="1.6" stroke-linecap="round"/>
<rect x="349" y="265" width="86" height="68" fill="none" stroke="#FFC857" stroke-width="2"/>
<rect x="452" y="252" width="88" height="78" fill="#1B2745" stroke="#8190AE" stroke-width="1.4"/>
<path d="M489 266 h14 v10 h10 v14 h-10 v10 h-14 v-10 h-10 v-14 h10 z" fill="#DCE3EF"/>
<rect x="489" y="310" width="14" height="20" fill="#0A1328"/>
<path d="M528 252 V224" stroke="#DCE3EF" stroke-width="1.6" stroke-linecap="round"/>
<g fill="none" stroke-linecap="round">
<path d="M96 252 Q150 112 280 46 Q392 96 420 234" stroke="#D0202A" stroke-opacity=".28" stroke-width="10"/>
<path d="M96 252 Q150 112 280 46 Q392 96 420 234" stroke="#E0303A" stroke-width="3"/>
</g>
<g fill="#FFC857">
<circle cx="96" cy="252" r="4"/>
<circle cx="280" cy="46" r="4"/>
<circle cx="420" cy="234" r="4"/>
</g>
<g><circle cx="70" cy="238" r="14" fill="#FFC857"/><text class="mp-num" x="70" y="243.5" text-anchor="middle">1</text></g>
<g><circle cx="308" cy="34" r="14" fill="#FFC857"/><text class="mp-num" x="308" y="39.5" text-anchor="middle">2</text></g>
<g><circle cx="394" cy="222" r="14" fill="#FFC857"/><text class="mp-num" x="394" y="227.5" text-anchor="middle">3</text></g>
<g><circle cx="378" cy="317" r="14" fill="#FFC857"/><text class="mp-num" x="378" y="322.5" text-anchor="middle">4</text></g>
<text class="mp-label" x="62" y="358" text-anchor="middle" style="font-size:var(--mp-fs,17px)"><?php echo esc_html__( 'Shelter', 'spokares' ); ?></text>
<text class="mp-label" x="387" y="358" text-anchor="middle" style="font-size:var(--mp-fs,17px)"><?php echo esc_html__( 'County EOC', 'spokares' ); ?></text>
<text class="mp-label" x="502" y="358" text-anchor="middle" style="font-size:var(--mp-fs,17px)"><?php echo esc_html__( 'Hospital', 'spokares' ); ?></text>
<text class="mp-label" x="262" y="24" text-anchor="end" style="font-size:var(--mp-fs,17px)"><?php echo esc_html__( 'W7GBU repeater', 'spokares' ); ?></text>
<g style="transform-box:fill-box;transform-origin:100% 0;transform:scale(var(--mp-pill,1))">
<rect x="434" y="12" width="114" height="30" rx="15" fill="#0E1A33" fill-opacity=".9" stroke="#FFC857" stroke-opacity=".75"/>
<circle cx="451" cy="27" r="4" fill="#FFC857"/>
<text class="mp-pilltext" x="461" y="32"><?php echo esc_html__( 'A scenario', 'spokares' ); ?></text>
</g>
</svg>
</figure>
