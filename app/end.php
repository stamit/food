<?

--$DEPTH;

if ( $DEPTH<=0 && !$INLINE_REQUEST ) {

?></td></tr>
<tr class="footer"><td class="footer">
	<?
		list($usec,$sec) = explode(' ', microtime());
		$STOPWATCH = ((float)$usec + (float)$sec) - $STOPWATCH;
		print intval($STOPWATCH*1000);
	?>ms
</td></tr>
</table>

</body>

<?
	return $ID;
} else if ( $DEPTH>1 || !$INLINE_REQUEST ) {
	return pop();
}

?>
