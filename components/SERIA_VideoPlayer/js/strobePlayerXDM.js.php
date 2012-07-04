<?php
	$iframeUrl = $_GET["iframeUrl"];
	$containerId = $_GET["containerId"];
?>
//$(function() {
/*
	var t = new easyXDM.Socket({
		remote: "<?php echo urldecode($iframeUrl); ?>",
		container: document.getElementById("<?php echo $containerId; ?>"),
		props: {

			style: {
				width: containerWidth,
				height: containerHeight
			}

		}
	});
	function getCurrentTime()
	{
	       t.postMessage("Yay, it works!");
	}
*/
//});
var xhr = new easyXDM.Rpc({
	remote: "<?php echo urldecode($iframeUrl); ?>",
	container: document.getElementById("<?php echo $containerId; ?>"),
	props: {
            style: {
                border: "2px solid red",
                width: "200px",
                height: "300px",
            }
        }
	}, {
	    remote: {
        test: {}
    }
});

function joakimAlertCurrentTime()
{
	xhr.test(function(r) {
		return r;
	}, function(r) {
		alert("FAILED");
	});
}
