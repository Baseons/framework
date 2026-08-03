<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        {{ $include }}
        <title>Error exception</title>
    </head>

    <body>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
                outline: none;
                -webkit-tap-highlight-color: rgba(255, 255, 255, 0);
                scroll-behavior: smooth;
                overscroll-behavior: none;
                line-height: 1;
                text-rendering: optimizeLegibility;
                -webkit-font-smoothing: antialiased;
                -moz-osx-font-smoothing: grayscale;
                image-rendering: crisp-edges;
                image-rendering: -webkit-optimize-contrast;
                font-family: -apple-system, BlinkMacSystemFont,
                    "Segoe UI", "Roboto", "Oxygen",
                    "Ubuntu", "Cantarell", "Fira Sans",
                    "Droid Sans", "Helvetica Neue", sans-serif;
            }

            :root {
                color-scheme: light;
                --background-body: rgb(25 25 25);
                --border: 1px solid #979797;
                --color-theme: rgb(40, 133, 199);
                --color-1: rgb(195, 195, 195);
                --box-shadow-1: 0 0 3px 2px black inset;
            }

            ::-webkit-scrollbar {
                width: 6px;
                height: 0;
                border-radius: 5px;
                background-color: transparent;
            }

            ::-webkit-scrollbar-thumb {
                border-radius: 5px;
                background-color: rgba(130, 130, 130, 0.15);
            }

            ::selection {
                background-color: var(--color-theme);
                color: var(--color-1);
            }

            body {
                background-color: var(--background-body);
            }

            .hidden {
                display: none !important;
            }

            .not-select {
                -webkit-touch-callout: none !important;
                -webkit-user-select: none !important;
                -khtml-user-select: none !important;
                -moz-user-select: none !important;
                -ms-user-select: none !important;
                user-select: none !important;
            }

            .not-select * {
                -webkit-touch-callout: none !important;
                -webkit-user-select: none !important;
                -khtml-user-select: none !important;
                -moz-user-select: none !important;
                -ms-user-select: none !important;
                user-select: none !important;
            }

            /* header */
            header {
                background-color: rgb(15 15 15);
                display: flex;
                box-shadow: var(--border);
                display: flex;
                flex-direction: column;
                height: 210px;
                box-shadow: 0 0 5px 2px #00000099;
            }

            .error-message {
                width: 100%;
                margin: 0 auto;
                color: white;
                font-size: 18px;
                font-weight: 500;
                display: flex;
                justify-content: center;
                align-items: center;
                background-color: darkred;
                text-align: center;
                box-shadow: var(--box-shadow-1);
                padding: 12px;
                height: 150px;
                line-height: 1.4;
            }

            .error-link {
                display: flex;
                width: 100%;
                padding: 0 6px;
                color: var(--color-theme);
                font-size: 14px;
                text-decoration: none;
                cursor: pointer;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                height: 30px;
                align-items: center;
                line-height: 1;
            }

            /* menu */
            menu {
                display: flex;
                width: 100%;
                width: 100%;
                height: 30px;
                box-shadow: 0 2px 2px 0px black;
            }

            menu button {
                border: none;
                background-color: unset;
                padding: 8px;
                font-size: 12px;
                cursor: pointer;
                color: var(--color-1);
                font-weight: 500;
                line-height: 1;
            }

            .view {
                width: calc(100vw - 6px);
                height: calc(100vh - 226px);
                overflow: auto;
            }

            main {
                padding: 8px 8px 8px 0;
                width: 100%;
            }

            menu button:hover,
            .menu-active {
                color: var(--color-theme);
            }

            /* code */
            .debug-box {
                display: flex;
                align-items: start;
                position: relative;
                width: 100%;
                height: 100%;
            }

            .debug-box * {
                font-size: 14px;
            }

            .debug-body {
                display: flex;
                align-items: start;
                position: relative;
                width: 100%;
                height: 100%;
                overflow: auto;
            }

            .debug-lines {
                display: flex;
                flex-direction: column;
                width: auto;
                justify-content: flex-start;
                margin-right: 5px;
                position: sticky;
                left: 0;
                background-color: var(--background-body);
            }

            .debug-line {
                text-decoration: unset;
                position: relative;
                cursor: pointer;
                width: auto;
                padding: 0 6px 0 6px;
                margin: 0;
                color: rgb(136, 136, 136);
                color: #ccc;
                background: 0 0;
                text-align: left;
                white-space: pre;
                word-spacing: normal;
                word-break: normal;
                word-wrap: normal;
                line-height: 1.5;
                -moz-tab-size: 4;
                -o-tab-size: 4;
                tab-size: 4;
                -webkit-hyphens: none;
                -moz-hyphens: none;
                -ms-hyphens: none;
                hyphens: none;
                display: flex;
                align-items: center;
                -ms-user-select: none !important;
                user-select: none !important;
            }

            .debug-line:hover,
            .debug-line-error,
            .debug-line-hover {
                background-color: rgba(232, 232, 232, 0.12) !important;
            }

            .debug-line-error {
                color: rgb(201, 70, 70);
            }

            .debug-marker-error {
                position: absolute;
                background-color: rgba(139, 0, 0, 0.10);
                display: none;
                z-index: -10;
            }

            .debug-line-hover {
                color: var(--color-theme)
            }

            .debug-marker-hover {
                position: absolute;
                background-color: rgba(0, 81, 139, 0.10);
                display: none;
                z-index: -10;
            }

            /* trace */
            .debug-infos {
                width: 30%;
                max-width: 400px;
                min-width: 300px;
                height: 100%;
                display: flex;
                flex-direction: column;
                gap: 8px;
                padding: 1px 5px 0 5px;
                overflow: auto;
            }

            .debug-infos * {
                text-decoration: unset;
            }

            .debug-trace {
                display: flex;
                flex-direction: column;
                background-color: #0f0f0f70;
                padding: 6px;
                gap: 2px;
                border-radius: 4px;
                box-shadow: 0 0px 2px 1px #00000069;
            }

            .debug-trace[href]:hover {
                background-color: #2525254a;
            }

            .debug-trace[href]:hover .trace-link {
                text-decoration: underline
            }

            .trace-info {
                display: flex;
                gap: 5px;
                align-items: center;
            }

            .trace-info p {
                text-overflow: clip;
                font-size: 14px;
            }

            .trace-info p:first-child {
                text-transform: capitalize;
                color: var(--color-theme)
            }

            .trace-info p:last-child {
                color: rgba(255, 255, 255, 0.824);
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .general {
                padding: 8px;
                border: 1px solid #333333;
                background-color: rgb(30 30 30 / 29%);
                border-radius: 6px;
                display: flex;
                flex-direction: column;
                gap: 4px;
                box-shadow: 0 0px 2px 1px #00000069;
            }

            .general p {
                font-size: 12px;
                color: var(--color-theme)
            }

            .general-info {
                display: flex;
                width: 100%;
                justify-content: space-between
            }

            table {
                margin: -6px 4px 0 6px;
                border-spacing: 0 8px;
            }

            table tr {
                box-shadow: 0 0 2px 1px #000000a6;
            }

            table tr th {
                text-align: start;
                font-size: 14px;
                align-items: start;
            }

            table tr th:first-child {
                color: var(--color-theme);
                font-weight: 500;
                background-color: #151515;
                white-space: nowrap;
                padding: 8px;
            }

            table tr th:last-child {
                color: var(--color-theme);
                font-weight: bold;
                background-color: #151515;
                width: 100%;
            }

            .dd-code {
                margin: 0 auto !important;
                max-height: 280px;
                overflow: auto;
                border-radius: unset !important;
            }

            .dd-code::-webkit-scrollbar-thumb {
                border-radius: 0 !important;
            }

            @media screen and (max-width: 850px) {
                .error-message {
                    font-size: 16px;
                }

                .debug-box * {
                    font-size: 12px;
                }

                .debug-infos {
                    display: none !important;
                }
            }

        </style>

        <p class="hidden" id="code">{{ $file }}</p>
        <p class="hidden" id="error_line">{{ $error->getLine() }}</p>
        <p class="hidden" id="error_file">{{ $error->getFile() }}</p>

        <header>
            <a class="error-link" href="vscode://file{{ $error->getFile() }}:{{ $error->getLine() }}">{{ $error->getFile() }} - {{ $error->getLine() }}</a>
            <h1 class="error-message">{{ $error->getMessage() }}</h1>

            <menu id="menu">
                <button data-view="view-debug" class="menu-active">DEBUG</button>
                @if(count($request['all']))<button data-view="view-request">REQUEST</button> @endif
                @if(count($request['session']))<button data-view="view-session">SESSION</button> @endif
                @if(count($request['header']))<button data-view="view-header">HEADER</button> @endif
                @if(count($request['cookie']))<button data-view="view-cookie">COOKIE</button> @endif
                @if(count($server))<button data-view="view-server">SERVER</button> @endif
            </menu>
        </header>

        <main>
            <div id="view-debug" class="view">
                <div class="debug-box">
                    <div class="debug-infos">
                        <div class="general">
                            <div class="general-info">
                                <p>PHP VERSION</p>
                                <p>{{ phpversion() }}</p>
                            </div>

                            <div class="general-info">
                                <p>REQUEST METHOD</p>
                                <p>{{ $request['class']->method() }}</p>
                            </div>

                            <div class="general-info">
                                <p>REQUEST LENGTH</p>
                                <p>{{ $server['CONTENT_LENGTH'] }}</p>
                            </div>

                            <div class="general-info">
                                <p>EXECUTION TIME</p>
                                <p>{{ $ms }}</p>
                            </div>
                        </div>

                        @foreach ($error->getTrace() as $trace)
                        <a {{ !empty($trace['file']) ? "href=\" vscode://file{$trace['file']}:{$trace['line']}\"" : '' }} class="debug-trace">
                            @foreach($trace as $trace_name => $trace_value)
                            @if(is_string($trace_value))
                            <div class="trace-info">
                                <p>{{ $trace_name }}</p>
                                <p @if($trace_name=='file' ) class="trace-link" @endif>{{ $trace_value }}</p>
                            </div>
                            @endif
                            @endforeach
                        </a>
                        @endforeach
                    </div>

                    <div class="debug-body">
                        <div class="debug-marker-error"></div>
                        <div class="debug-marker-hover"></div>
                        <div class="debug-lines"></div>

                        <div class="debug-content">
                            <div class="debug-toolbar">
                                <pre class="line-numbers" class="language-php"><code class="language-php formated"></code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if(count($request['all']))
            <div id="view-request" class="view hidden">
                <table>
                    @foreach ($request['all'] as $name => $value)
                    <tr>
                        <th>{{ $name }}</th>
                        <th>
                            {{ dump($value) }}
                        </th>
                    </tr>

                    @endforeach
                </table>
            </div>
            @endif

            @if(count($request['session']))
            <div id="view-session" class="view hidden">
                <table>
                    @foreach ($request['session'] as $name => $value)
                    <tr>
                        <th>{{ $name }}</th>
                        <th>
                            {{ dump($value) }}
                        </th>
                    </tr>

                    @endforeach
                </table>
            </div>
            @endif

            @if(count($request['header']))
            <div id="view-header" class="view hidden">
                <table>
                    @foreach ($request['header'] as $name => $value)
                    <tr>
                        <th>{{ $name }}</th>
                        <th>
                            {{ dump($value) }}
                        </th>
                    </tr>

                    @endforeach
                </table>
            </div>
            @endif

            @if(count($request['cookie']))
            <div id="view-cookie" class="view hidden">
                <table>
                    @foreach ($request['cookie'] as $name => $value)
                    <tr>
                        <th>{{ $name }}</th>
                        <th>
                            {{ dump($value) }}
                        </th>
                    </tr>
                    @endforeach
                </table>
            </div>
            @endif

            @if(count($server))
            <div id="view-server" class="view hidden">
                <table>
                    @foreach ($server as $name => $value)
                    <tr>
                        <th>{{ $name }}</th>
                        <th>
                            {{ dump($value) }}
                        </th>
                    </tr>
                    @endforeach
                </table>
            </div>
            @endif
        </main>

        <script>
            function clickScrollX(event) {
                let container = event.currentTarget;

                if (!container || event.type != 'mousedown') return;

                let isDragging = false;
                let startX;
                let scrollLeft;
                let time = undefined;

                time = setTimeout(() => {
                    let selecion = window.getSelection();
                    if (selecion && selecion.toString().trim().length) return;

                    container.classList.add('not-select');
                    isDragging = true;
                    startX = event.clientX;
                    scrollLeft = container.scrollLeft;
                    container.style.cursor = 'grabbing';

                    let containerMove = (e) => {
                        if (!isDragging) return;

                        container.scrollLeft = scrollLeft - (e.clientX - startX);
                    };

                    let containerUp = () => {
                        container.removeEventListener('mousemove', containerMove);
                        container.removeEventListener('mouseup', containerUp);
                        container.removeEventListener('mouseleave', containerLeave);

                        container.style.cursor = '';
                        isDragging = false;

                        container.classList.remove('not-select');
                    };

                    let containerLeave = () => {
                        if (!isDragging) return;

                        isDragging = false;
                        container.style.cursor = '';
                        container.classList.remove('not-select');
                    };

                    container.addEventListener('mousemove', containerMove);
                    container.addEventListener('mouseup', containerUp);
                    container.addEventListener('mouseleave', containerLeave);
                }, 200);

                let clear = () => {
                    clearTimeout(time);

                    container.removeEventListener('mouseup', clear);
                };

                container.addEventListener('mouseup', clear);
            }

            document.addEventListener("DOMContentLoaded", function(event) {
                let views = document.querySelectorAll('.view');
                let buttons = document.querySelectorAll('#menu button');

                let error_line = Number(document.getElementById('error_line').innerText);
                let error_file = document.getElementById('error_file').innerText;
                let formated = document.querySelector('.formated');
                let code_box = document.querySelector('.debug-box');
                let code_marker_error = document.querySelector('.debug-marker-error');
                let code_marker_hover = document.querySelector('.debug-marker-hover');
                let code_lines = document.querySelector('.debug-lines');
                let code_body = document.querySelector('.debug-body');

                buttons.forEach((b) => {
                    b.addEventListener('click', (e) => {
                        let element = e.currentTarget;

                        document.querySelector('.menu-active').classList.remove('menu-active');
                        element.classList.add('menu-active');

                        views.forEach((v) => v.classList.add('hidden'));

                        setTimeout(() => {
                            document.getElementById(element.dataset.view).classList.remove('hidden');
                        }, 50);
                    });
                });

                let code = document.getElementById('code').innerText.split('\n').map((line, index, array) => {
                    return index === array.length - 1 ? line : line + '\n';
                });

                let displayMarkerError = () => {
                    let x = document.getElementById(`line_${error_line}`);

                    if (!x) return;

                    let line_height = x.clientHeight;
                    let code_marker_error_top = (x.getBoundingClientRect().top - code_body.getBoundingClientRect().top + code_body.scrollTop);

                    code_marker_error.style.top = `${code_marker_error_top}px`;
                    code_marker_error.style.height = `${line_height}px`;
                    code_marker_error.style.width = `${code_body.scrollWidth}px`;
                    code_marker_error.style.display = 'flex';
                }

                let displayMarkerHover = (e) => {
                    let rect = code_body.getBoundingClientRect();
                    let mouseX, mouseY;
                    let x = document.getElementById(`line_${error_line}`);

                    if (!x) return;

                    let line_height = x.clientHeight;

                    if (e.type === 'mousemove') {
                        mouseX = e.clientX - rect.left + code_body.scrollLeft;
                        mouseY = e.clientY - rect.top + code_body.scrollTop;
                    } else if (e.type === 'touch') {
                        let touch = e.touches[0];
                        mouseX = touch.clientX - rect.left + code_body.scrollLeft;
                        mouseY = touch.clientY - rect.top + code_body.scrollTop;
                    }

                    let y_line = parseInt(mouseY / (line_height)) + 1;
                    let y = document.getElementById(`line_${y_line}`);

                    if (!y) return;

                    document.querySelectorAll(`.debug-line-hover:not(#line_${error_line})`).forEach((i) => {
                        i.classList.remove('debug-line-hover');
                    })

                    if (y_line == error_line) {
                        code_marker_hover.style.display = 'none';

                        return;
                    }

                    let code_marker_hover_top = (y.getBoundingClientRect().top - code_body.getBoundingClientRect().top + code_body.scrollTop);

                    document.getElementById(`line_${y_line}`).classList.add('debug-line-hover');

                    code_marker_hover.style.top = `${code_marker_hover_top}px`;
                    code_marker_hover.style.height = `${line_height}px`;
                    code_marker_hover.style.width = `${code_body.scrollWidth}px`;
                    code_marker_hover.style.display = 'flex';
                }

                code_body.addEventListener('mousemove', displayMarkerHover);
                code_body.addEventListener('touch', displayMarkerHover);
                code_body.addEventListener('mousedown', clickScrollX);

                code.forEach((value, key) => {
                    let c_line = document.createElement('a');
                    let c_key = key + 1;

                    c_line.href = `vscode://file${error_file}:${c_key}`;
                    c_line.id = `line_${c_key}`;
                    c_line.dataset.value = value;
                    c_line.innerText = (c_key).toString();
                    c_line.classList.add('debug-line');
                    code_lines.append(c_line);

                    if (error_line == c_key) {
                        c_line.classList.add('debug-line-error');

                        setTimeout(function() {
                            c_line.scrollIntoView({
                                block: 'center'
                            });

                            displayMarkerError();
                        }, 100);
                    }
                });

                let x = Prism.highlight(code.join(''), Prism.languages.php, 'php');

                formated.innerHTML = x;
            })

        </script>
    </body>

</html>
