<style>
    .dd * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        outline: 0;
        -webkit-tap-highlight-color: rgba(255, 255, 255, 0);
        scroll-behavior: smooth;
        color: #d2721e;
        font-family: monospace;
        line-height: 1.2;
        font-size: 14px;
        word-break: break-word;
    }

    .dd-code,
    .dd-title {
        display: flex;
        width: 100%;
    }

    .dd-code-var {
        color: #d2721e
    }

    .dd p {
        width: fit-content
    }

    .dd-title {
        margin: 0 auto;
        padding: 0 10px 10px 0;
        color: rgb(40, 133, 199);
        font-weight: normal;
        text-decoration: unset;
        font-family: sans-serif;
        letter-spacing: 1px;
    }

    .dd-code {
        flex-direction: column;
        background: #1b1b1b;
        margin: 0 auto 8px;
        padding: 10px;
        border-radius: 6px
    }

    .dd-code-array-key {
        color: #5791d8;
        font-weight: lighter !important
    }

    .dd-code-string-value {
        color: #13b313;
        font-weight: 600;
        font-size: 13px
    }

    .dd-code-arrow {
        color: #cdcdcd !important
    }

    .dd-code-type {
        color: #5f5f5f !important;
        cursor: pointer
    }

    .dd-code-tags,
    .dd-code-type:hover {
        color: #d2721e !important
    }

    .dd .display-none {
        display: none !important
    }
</style>

<div id="{{ $id }}" class="dd">
    @yield('title')

    {{ $dd }}
</div>

<script>
    var id = '{{ $id }}';
    var types = document.querySelectorAll(`#${id} .dd-code-type`);
    var copies = document.querySelectorAll(`#${id} .dd-copy`);

    if (types.length) types.forEach(function(e) {
        e.addEventListener('click', function(c) {
            c.stopPropagation();

            var token = c.currentTarget.dataset.token || null;
            var ocult = c.currentTarget.dataset.ocult || 0;
            if (!token) return;

            var lines = document.querySelectorAll(`.${token}`);

            if (!lines) return;

            lines.forEach(function(l) {
                if (ocult == 1) {
                    l.classList.remove('display-none');
                    c.target.dataset.ocult = 0;
                } else {
                    l.classList.add('display-none');
                    c.target.dataset.ocult = 1;
                }
            });
        });
    });
</script>