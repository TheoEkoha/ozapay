import notifyDefaultOptions from "../ui/NotifyOptions.js"

export default function configureHandler(handler) {
    handler.registerAction('call_jquery_webcomponent', (params) => {
        for (let element of $(params.selector)) {
            element = $(element)
            if (typeof element[params.method] === 'undefined') {
                console.warn('Function ' + params.method + '() doesn\'t exist for custom element ', element)
            } else {
                element[params.method](...params.method_params)
            }
        }
    });

    handler.registerAction('call_reload_datatable', (params) => {
        for (let element of $(params.selector)) {
          element.grid.forceRender();
        }
    });

    handler.registerAction('show_modal_form', (e) => {
        const divContainer = document.createElement("div");
        divContainer.innerHTML = e.value.trim();
        divContainer.firstChild.id = "umbrella-modal";
        let umbrellaModal = document.getElementById("umbrella-modal");

        if (umbrellaModal) {
            umbrellaModal.innerHTML = divContainer.firstChild.innerHTML;
        } else {
            umbrellaModal = divContainer.firstChild;
            umbrellaModal.addEventListener("hidden.bs.modal", umbrellaModal.remove);
            document.body.appendChild(umbrellaModal);
            new bootstrap.Modal(umbrellaModal).show();
        }
        initRender()
    });

    handler.registerAction('call_js_function', (params) => {
        if (functions[params.function] === undefined) {
            console.log(functions[params.function], 'is not a function')
            return false;
        }

        if (typeof functions[params.function] === 'function') {
            functions[params.function](...params.arguments)
        } else {
            console.log(functions[params.function], 'is not a function')
            return false;
        }
    });

    handler.registerAction('call_reload_page', (params) => {
        location.reload();
    });

    handler.registerAction('hide_spinner', (params) => {
        umbrella.Spinner.hide()
    });

    handler.registerAction('show_toast', (params) => {
        umbrella.Toast.show(params['type'], params['text'], params['title'], params['options']);
    });

    handler.registerAction('show_notify', (params) => {
        $.notify({
            title: params['title'],
            message: params['text']
        }, {...notifyDefaultOptions, ...{type: params['type']}, ...params['options']});
    });

    handler.registerAction('show_modal', (params) => {

        const template = document.createElement('div')
        template.innerHTML = params.value.trim()
        template.firstChild.id = 'umbrella-modal'

        let modalElement = document.getElementById('umbrella-modal')

        if (modalElement) {
            modalElement.innerHTML = template.firstChild.innerHTML
        } else {
            modalElement = template.firstChild
            modalElement.addEventListener('hidden.bs.modal', modalElement.remove)
            document.body.appendChild(modalElement)
            const modal = new bootstrap.Modal(modalElement)
            modal.show()
        }
    });

    handler.registerAction('close_modal', (params) => {
        const modalElement = document.getElementById('umbrella-modal')
        if (modalElement) {
            const modal = bootstrap.Modal.getInstance(modalElement)
            if (modal) {
                modal.hide()
            }
        }
    });

    handler.registerAction('show_offcanvas', (params) => {

        const template = document.createElement('div')
        template.innerHTML = params.value.trim()
        template.firstChild.id = 'umbrella-offcanvas'

        let offcanvasElement = document.getElementById('umbrella-offcanvas')

        if (offcanvasElement) {
            offcanvasElement.innerHTML = template.firstChild.innerHTML
        } else {
            offcanvasElement = template.firstChild
            offcanvasElement.addEventListener('hidden.bs.offcanvas', offcanvasElement.remove)
            document.body.appendChild(offcanvasElement)
            const offcanvas = new bootstrap.Offcanvas(offcanvasElement)
            offcanvas.show()
        }
    });

    handler.registerAction('close_offcanvas', (params) => {
        const offcanvasElement = document.getElementById('umbrella-offcanvas')
        if (offcanvasElement) {
            const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasElement)
            if (offcanvas) {
                offcanvas.hide()
            }
        }
    });

    handler.registerAction('eval', (params) => {
        eval(params.value);
    });

    handler.registerAction('redirect', (params) => {
        window.location = params.value;
    });

    handler.registerAction('reload', (params) => {
        window.location.href = window.location.href.split('#')[0];
    });

    handler.registerAction('update', (params) => {
        document.querySelectorAll(params.selector).forEach((e) => {
            e.innerHTML = params.value.trim()
        })
    });

    handler.registerAction('remove', (params) => {
        document.querySelectorAll(params.selector).forEach((e) => {
            e.remove()
        })
    });

    handler.registerAction('call_webcomponent', (params) => {
        for (let element of document.querySelectorAll(params.selector)) {
            if (typeof element[params.method] === 'undefined') {
                console.warn('Function ' + params.method + '() doesn\'t exist for custom element ', element)
            } else {
                element[params.method](...params.method_params)
            }
        }
    });

    handler.registerAction('download', (params) => {
        const link = document.createElement('a')

        link.href = URL.createObjectURL(new Blob([params.content]));

        if (params.filename) {
            link.download = params.filename
        }

        link.click()
    });

    handler.setErrorHandler((requestObject, error, errorThrown) => {
        if (requestObject.status === 401) {
            umbrella.Toast.warning('401 - ' + umbrella.Translator.trans('unauthorized_error'));

        } else if (requestObject.status === 403) {
            umbrella.Toast.warning('403 - ' + umbrella.Translator.trans('forbidden_error'));

        } else if (requestObject.status === 404) {
            umbrella.Toast.warning('404 - ' + umbrella.Translator.trans('notfound_error'));

        } else {
            umbrella.Toast.error(umbrella.Translator.trans('other_error'));
        }
    })

}
