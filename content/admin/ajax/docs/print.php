<?php defined( '_DOIT' ) or die( 'Restricted access' );

$rtrn = '
<style>
	@media print {
		@page {size:auto; size: A4 portrait; margin:0;}
	}
</style>
<script>window.print();</script>
';

echo $rtrn;

?>