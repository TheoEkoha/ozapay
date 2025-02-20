// --- Bootstrap

window.bootstrap = {
    Tooltip : bootstrap.Tooltip,
    Modal: bootstrap.Modal,
    Offcanvas: bootstrap.Offcanvas,
    Popover: bootstrap.Popover,
}

// --- Umbrella
import Translator from './translator/Translator.js';
import Spinner from './ui/Spinner.js'
import ConfirmModal from './ui/ConfirmModal.js'
import Toast from './ui/Toast.js'

const LANG = document.querySelector('html').getAttribute('lang')

window.umbrella = {
    LANG: LANG,
    Translator : new Translator(LANG),
    Spinner : Spinner,
    ConfirmModal : ConfirmModal,
    Toast : Toast
}

// --- JsResponseHandler
import JsResponseHandler from './jsresponse/JsResponseHandler.js';
import configureHandler from './jsresponse/Configure.js'

const jsResponseHandler = new JsResponseHandler();
configureHandler(jsResponseHandler);

window.umbrella.jsResponseHandler = jsResponseHandler

// --- Bind some elements
import BindUtils from './utils/BindUtils.js';
BindUtils.enableAll();