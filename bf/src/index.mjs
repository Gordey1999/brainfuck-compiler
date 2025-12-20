import {Editor} from "./Editor.mjs";
import {Profiler} from "./Profiler.mjs";
import {Console} from "./Console.mjs";
import {Controller} from "./Controller.mjs";
import {FileInput} from "./FileInput.mjs";
import {TabManager} from "./TabManager.mjs";

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
const tabManager = new TabManager(tabs, controller, editor, fileInput);


const buttonsBlock = document.querySelector('.buttons');

buttonsBlock.querySelector('.btn-run')
	.addEventListener('click', controller.onRun);
buttonsBlock.querySelector('.btn-stop')
	.addEventListener('click', controller.onStop);
buttonsBlock.querySelector('.btn-debug')
	.addEventListener('click', controller.onDebug);
buttonsBlock.querySelector('.btn-step')
	.addEventListener('click', controller.onStep);
buttonsBlock.querySelector('.btn-line')
	.addEventListener('click', controller.onStepLine);
buttonsBlock.querySelector('.btn-out')
	.addEventListener('click', controller.onStepOut);
buttonsBlock.querySelector('.btn-input')
	.addEventListener('click', fileInput.onToggle);

window.MyEditor = editor;

