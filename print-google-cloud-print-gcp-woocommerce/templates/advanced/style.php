<?php
/* @var int $fontSize
 * @var int|string $fontWeight
 * @var int $headerSize
 * @var int|string $headerWeight
 */

$calculate_header_size = function (float $ratio) use ($headerSize, $fontSize): int {
	return max(
		ceil($headerSize * $ratio),
		$fontSize + 2
	);
};

$css = <<<CSS
html {
	font-size: {$fontSize}px;
}

body {
	width: 100%;
	font-size: 100%;
	font-weight: {$fontWeight};
	margin: 0;
	font-family: Arial, sans-serif;
}

* {
	-webkit-print-color-adjust: exact;
	max-width: 100%;
	box-sizing: border-box;
}

header,
table,
footer,
div.label-solid {
	width: 90%;
	margin-left: auto;
	margin-right: auto;
}

header {
	margin-bottom: 1.5rem;
}

footer {
	margin-top: 1.5rem;
}

div.label-solid {
	max-width: 32rem;
	padding: 1rem;
	border: 2px solid #000;
}

.uppercase {
	text-transform: uppercase;
}

h1, h2, h3, h4, h5, h6 {
	margin: 0;
}

h1:not(:last-child),
h2:not(:last-child),
h3:not(:last-child),
h4:not(:last-child),
h5:not(:last-child),
h6:not(:last-child) {
	margin: 0 0 0.75em;
}

h1, h2, h3, h4, h5, h6, th {
	font-weight: {$headerWeight};
}

h1 {
	font-size: {$headerSize}px;
}

h2 {
	font-size: {$calculate_header_size(0.8)}px;
}

h2.order-id {
	font-weight: {$fontWeight};
}

h3 {
	font-size: {$calculate_header_size(0.65)}px;
}

h4,
th {
	font-size: {$calculate_header_size(0.5)}px;
}

h5,
h6 {
	font-size: {$calculate_header_size(0.4)}px;
}

.logo {
	max-width: 21.4rem;
	max-height: 14.2rem;
	margin-right: 1rem;
	float: left;
}

table {
	font-size: inherit;
	border-spacing: 0;
}

th {
	text-align: left;
}

th, td {
 	padding: 0;
}

table.details td {
	vertical-align: top;
}

table.details tr + tr th,
table.details tr + tr td {
	padding-top: 1.5rem;
}

table.details th + th,
table.details td + td {
	padding-left: 1rem;
}

td.w-70 {
	width: 70%;
}

td.display-content-center {
	text-align: center;
}

td.display-content-center > * {
	display: inline-block;
}

td.display-content-center > * > * {
	text-align: left;
}

td.company h3 {
	margin-bottom: 0.5rem;
}

table.products {
	margin-top: 2rem;
}

table.products th,
table.products td {
	padding: 0.75rem;
}

table.products th,
table.products tbody td {
	border-bottom: 2px solid #EBEBEB;
}

table.products tfoot tr:last-child:not(:first-child) td:not(:first-child) {
	border-top: 2px solid #EBEBEB;
}

table.products tr.total td {
	font-size: 1.15rem;
}

table.products tr.total td:last-child {
	font-weight: bold;
}

table.products img {
	max-width: 3.6rem;
	max-height: 3.6rem;
	border-radius: 0.7rem;
}

table.products-solid th,
table.products-solid td {
	text-align: center;
}

table.products-solid th {
	color: #fff;
	background: #22252A;
	border-bottom: 0;
}

table.products-solid th:not(:last-child) {
	border-right: 2px solid #0B0E0F;
}

table.products-solid td {
	border-right: 2px solid #E8E8E8;
	border-right: 2px solid #E8E8E8;
}

table.products-solid td:first-child {
	border-left: 2px solid #E8E8E8;
}

ul {
	list-style: none;
	padding: 0;
	margin: 0;
}

ul li:not(:last-child) {
	margin-bottom: 0.25em;
}

ul h2, ul h3, ul h4, ul h5, ul h6 {
	margin: 0;
}
CSS;
echo $css;
