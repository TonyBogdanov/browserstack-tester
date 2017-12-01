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
            failures.push(test.fullTitle() + ' :: [' + e.name + '] ' + e.message);
        });

        runner.on('end', function() {
            var _s = document.location.search.match(/_s=([a-f0-9]+)/);
            if (!_s) {
                bstError('Missing session identifier');
            }

            var frame = document.createElement('frame'),
                form = document.createElement('form'),
                session = document.createElement('input');

            frame.src = 'about:blank';
            frame.name = 'bstreport';

            form.action = '/_report';
            form.method = 'POST';
            form.target = 'bstreport';

            session.type = 'hidden';
            session.name = 'session';
            session.value = _s[1];

            for (var i = 0; i < failures.length; i++) {
                var e = document.createElement('input');

                e.type = 'hidden';
                e.name = 'failures[]';
                e.value = failures[i];

                form.appendChild(e);
            }

            form.appendChild(session);
            document.body.appendChild(form);
            document.body.appendChild(frame);

            form.submit();

            document.body.dataset.bstReportReady = true;
            var event = document.createEvent('Event');
            event.initEvent('bst-report-ready', true, true);
            document.body.dispatchEvent(event);
        });
    }

    reporter.prototype.suiteURL = Mocha.reporters.HTML.prototype.suiteURL;
    reporter.prototype.testURL = Mocha.reporters.HTML.prototype.testURL;
    reporter.prototype.addCodeToggle = Mocha.reporters.HTML.prototype.addCodeToggle;

    mocha.reporter(reporter);
});