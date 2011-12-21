<!-- script language="javascript" type="text/javascript" src="wp-includes/js/jquery/jquery.js"></script -->
<script language="javascript" type="text/javascript">
	frax_addOnLoad(function() {
		frax_showRespond(sShowRespond);
	});
	function frax_showRespond(status) {
		if(status=='locked' && document.getElementById("commentform")) {
			var message = document.getElementById("commentform");
			do message = message.previousSibling;
			while (message && message.nodeType != 1);
			message.innerHTML = "To leave comments please unlock this post.";
			document.getElementById("commentform").innerHTML = "";
		}
	}
	function frax_addOnLoad(newFunction) {
		var oldOnload = window.onload;
		if (typeof oldOnload == 'function') {
	 	 window.onload = function() {
			if (oldOnload) {
				oldOnload();
			}
			newFunction();
	 	 }
		} else {
		  window.onload = newFunction;
		}
	}

</script>