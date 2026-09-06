import {Editor} from "./Editor";
import {Profiler} from "./Profiler";
import {Console} from "./Console";
import {FileInput} from "./FileInput";
import {Controller} from "./Controller";
import {Builder} from "./Builder";
import {TabManager} from "./TabManager";
import {Storage} from "./lib/Storage";
import {StorageController} from "./StorageController";
import {WindowsController} from "./WindowsController";

// npx rollup -c

const buildUrl = 'https://avito-work1.t-dir.dev/brainfuck/brainfix/ajax/compile.php';

const editorEl = document.querySelector('.edit-area') as HTMLElement;
const profilerEl = document.querySelector('.tracing-container') as HTMLElement;
const consoleEl = document.querySelector('.console-container') as HTMLElement;
const statusEl = document.querySelector('.console-status') as HTMLElement;
const counterEl = document.querySelector('.console-commands') as HTMLElement;
const input = document.querySelector('.console-input') as HTMLElement;
const tabs = document.querySelector('.tabs') as HTMLElement;
const saveModal = document.querySelector('.modal-save') as HTMLElement;
const loadModal = document.querySelector('.modal-load') as HTMLElement;

new WindowsController();

const editor = new Editor(editorEl);
const profiler = new Profiler(profilerEl, 500);
const console = new Console(consoleEl, statusEl, counterEl);
const fileInput = new FileInput(input);

const controller = new Controller(editor, profiler, console, fileInput);
const builder = new Builder(editor, console, buildUrl);

const tabManager = new TabManager(tabs, controller, editor, fileInput);

const storage = new Storage();
const storageController = new StorageController(saveModal, loadModal, storage, tabManager);

editor.onChange(storageController.onEditorChange);
editor.onChange(tabManager.onEditorChange);
builder.setTabManager(tabManager);

const nav = document.querySelector('.nav')!;
const buttonsBb = document.querySelector('.buttons-bb');

nav.querySelector('.btn-run')!.addEventListener('click', controller.onRun);
nav.querySelector('.btn-fast')!.addEventListener('click', controller.onFast);
nav.querySelector('.btn-stop')!.addEventListener('click', controller.onStop);
nav.querySelector('.btn-step')!.addEventListener('click', controller.onStep);
nav.querySelector('.btn-line')!.addEventListener('click', controller.onStepLine);
nav.querySelector('.btn-out')!.addEventListener('click', controller.onStepOut);

nav.querySelector('.btn-build')!.addEventListener('click', builder.onBuild);
nav.querySelector('.btn-build-min')!.addEventListener('click', builder.onBuildMin);
nav.querySelector('.btn-uglify')!.addEventListener('click', (e) => builder.onUglify(e as MouseEvent));

nav.querySelectorAll('.btn-input').forEach((el) => {
	el.addEventListener('click', fileInput.onToggle);
});
nav.querySelectorAll('.btn-save').forEach((el) => {
	el.addEventListener('click', storageController.onSave)
});
nav.querySelectorAll('.btn-load').forEach((el) => {
	el.addEventListener('click', storageController.onLoad)
});


//window.MyEditor = editor;

