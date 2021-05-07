// sprintf()
global.sprintf  = require("sprintf-js").sprintf;
global.vsprintf = require("sprintf-js").vsprintf;

// popper.js
import "@popperjs/core";

// Bootstrap 5
import bootstrap from "bootstrap";

// FontAwesome 5
import "@fortawesome/fontawesome-free";
import "@fortawesome/free-solid-svg-icons";
import "@fortawesome/free-regular-svg-icons";
import "@fortawesome/free-brands-svg-icons";

// SweetAlert 2
import Swap from "sweetalert2";

// Translations
import Translator from "bazinga-translator";

// Style sheets
import "./styles/app.scss";

// TypeScripts
import "./ts/index.ts";
import "./ts/ajax.ts";
import "./ts/alert.ts";
import "./ts/button.ts";
import "./ts/modal.ts";
import "./ts/toast.ts";
import "./ts/tooltip.ts";
import "./ts/translations.ts";
