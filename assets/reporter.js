window.onerror = function (e) {
    alert(e);
};

function bstError(message) {
    alert('BST: ' + message);
    throw new Error('BST: ' + message);
}

window.addEventListener('DOMContentLoaded', function () {
    if ('undefined' === typeof mocha || 'undefined' === typeof Mocha) {
        bstError('Mocha must be loaded during DOMContentLoaded');
    }

    function reporter(runner) {
        Mocha.reporters.HTML.apply(this, arguments);

        var failures = [];

        runner.on('fail', function(test, e) {
            failures.push(test.fullTitle() + ' // [' + e.name + '] ' + e.message);
        });

        runner.on('end', function() {
            var session = document.location.search.match(/_s=([a-f0-9]+)/);
            if (!session) {
                bstError('Missing session identifier');
            }

            var frame = document.createElement('iframe');

            frame.style.display = 'none';
            frame.src = '/_report?session=' + encodeURIComponent(session[1]) + '&failures=' +
                encodeURIComponent(JSON.stringify(failures));

            document.body.appendChild(frame);
        });
    }

    reporter.prototype.suiteURL = Mocha.reporters.HTML.prototype.suiteURL;
    reporter.prototype.testURL = Mocha.reporters.HTML.prototype.testURL;
    reporter.prototype.addCodeToggle = Mocha.reporters.HTML.prototype.addCodeToggle;

    mocha.reporter(reporter);
});