var callback = arguments[arguments.length - 1];
if ('undefined' !== typeof document.body.dataset.bstReportReady) {
    callback();
} else {
    document.body.addEventListener('bst-report-ready', function () {
        callback();
    }, false);
}