document.addEventListener('DOMContentLoaded', function () {
    const widgets = document.querySelectorAll('.wfc-widget');

    function closeAll() {
        widgets.forEach(function (widget) {
            widget.classList.remove('is-open');

            const button = widget.querySelector('.wfc-main-button');

            if (button) {
                button.setAttribute('aria-expanded', 'false');
            }
        });
    }

    widgets.forEach(function (widget) {
        const button = widget.querySelector('.wfc-main-button');

        if (!button) {
            return;
        }

        button.addEventListener('click', function (event) {
            event.stopPropagation();

            const isOpen = widget.classList.toggle('is-open');
            button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    });

    document.addEventListener('click', function (event) {
        const clickedInsideAny = Array.from(widgets).some(function (widget) {
            return widget.contains(event.target);
        });

        if (!clickedInsideAny) {
            closeAll();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeAll();
        }
    });
});