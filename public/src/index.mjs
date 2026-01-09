import {Editor} from "./Editor.mjs";
import {Profiler} from "./Profiler.mjs";
import {Console} from "./Console.mjs";
import {FileInput} from "./FileInput.mjs";
import {Controller} from "./Controller.mjs";
import {Builder} from "./Builder.mjs";
import {TabManager} from "./TabManager.mjs";

// node_modules/.bin/rollup public/src/index.mjs -f iife -o public/index.bundle.js -p @rollup/plugin-node-resolve

const editorEl = document.querySelector('.edit-area');
const profilerEl = document.querySelector('.tracing-container');
const consoleEl = document.querySelector('.console-container');
const statusEl = document.querySelector('.console-status');
const counterEl = document.querySelector('.console-commands');
const input = document.querySelector('.console-input');
const tabs = document.querySelector('.tabs');

const editor = new Editor(editorEl, '');
const profiler = new Profiler(profilerEl, 500);
const console = new Console(consoleEl, statusEl, counterEl);
const fileInput = new FileInput(input);

const controller = new Controller(editor, profiler, console, fileInput);
const builder = new Builder(editor, console);

const tabManager = new TabManager(tabs, controller, builder, editor, fileInput);
builder.setTabManager(tabManager);

const buttonsBf = document.querySelector('.buttons-bf');
const buttonsBb = document.querySelector('.buttons-bb');

buttonsBf.querySelector('.btn-run')
	.addEventListener('click', controller.onRun);
buttonsBf.querySelector('.btn-stop')
	.addEventListener('click', controller.onStop);
buttonsBf.querySelector('.btn-step')
	.addEventListener('click', controller.onStep);
buttonsBf.querySelector('.btn-line')
	.addEventListener('click', controller.onStepLine);
buttonsBf.querySelector('.btn-out')
	.addEventListener('click', controller.onStepOut);
buttonsBf.querySelector('.btn-input')
	.addEventListener('click', fileInput.onToggle);

buttonsBb.querySelector('.btn-build')
	.addEventListener('click', builder.onBuild);
buttonsBb.querySelector('.btn-build-min')
	.addEventListener('click', builder.onBuildMin);
buttonsBb.querySelector('.btn-uglify')
	.addEventListener('click', builder.onUglify)

window.MyEditor = editor;

