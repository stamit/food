<?

--$DEPTH;

if ( $DEPTH<=0 && !$INLINE_REQUEST ) {

?>

</div>
</div>
<div class="footerpush"></div>
</div>
<div class="footer">
	<?
		echo now(0)
	?> GMT - <?
		list($usec,$sec) = explode(' ', microtime());
		$STOPWATCH = ((float)$usec + (float)$sec) - $STOPWATCH;
		print intval($STOPWATCH*1000);
	?>ms
</div>
</body>

<?
	return $ID;
} else if ( $DEPTH>1 || !$INLINE_REQUEST ) {
	return pop();
}

?>
