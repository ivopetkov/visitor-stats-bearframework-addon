(function () {
    var log = function () {
        var data = {};
        data["url"] = window.location.toString();
        var referrer = "";
        try {
            var referrer = (new URL(document.referrer)).host;
        } catch (e) {
        }
        data["referrer"] = referrer;
        vsjs.log("load", data);
    };
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", log);
    } else {
        log();
    }
}());