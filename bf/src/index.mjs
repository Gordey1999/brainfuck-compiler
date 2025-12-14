import {Editor} from "./Editor.mjs";
import {Profiler} from "./Profiler.mjs";
import {Console} from "./Console.mjs";
import {Controller} from "./Controller.mjs";

const editor = new Editor(document.querySelector('.edit-area'), '');
const profiler = new Profiler(document.querySelector('.tracing-container'), 500);
const console = new Console(document.querySelector('.console-container'));
const controller = new Controller(editor, profiler, console);

window.MyEditor = editor;

