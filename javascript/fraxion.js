function showRespond(status) {
    if(status=='locked' && document.getElementById("commentform")) {
        var message = document.getElementById("commentform");
        do message = message.previousSibling;
        while (message && message.nodeType != 1);
        message.innerHTML = "To leave comments please unlock this post.";
        document.getElementById("commentform").innerHTML = "";
    }
}
window.onload = function() { showRespond(sShowRespond); }