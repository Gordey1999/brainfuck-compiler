import {Registry} from "./reducer/Registry";

export class Compiler {
    _commentSeparator = '#';
    _reduceRegistry = new Registry();

    constructor() {

    }

    compile(code: string) {
        code = this._sanitize(code);
        console.log(code);
        const reduceMap = this._reduceRegistry.build(code);
        const js = [];

        js.push(`
            const m = new Uint8Array(32768);
            let p = 0;
        `);

        let i = 0;

        while (i < code.length) {
            const char = code[i];

            const reducer = reduceMap.get(i);

            if (reducer) {
                js.push(reducer.compile());
                i += reducer.getLength();
            } else {
                js.push(this._compileChar(char));
                i++;
            }
        }

        js.push("self.postMessage({ type: 'DONE' });\n");

        return this._formatJs(js.join(''));
    }

    _sanitize(code: string): string {
        const lines = code.split("\n");

        const result = [];

        for (let i = 0; i < lines.length; i++) {
            const withoutComment = this._sanitizeComment(lines[i]);
            const sanitized = withoutComment.replace(/[^+<>\-[\].,]/g, '');
            result.push(sanitized);
        }

        return result.join('');
    }

    _sanitizeComment(line: string): string {
        return line.split(this._commentSeparator)[0];
    }

    _compileChar(char: string): string
    {
        if (char === '+') return "m[p]++;\n";
        if (char === '-') return "m[p]--;\n";
        if (char === '>') return "p++;\n";
        if (char === '<') return "p--;\n";
        if (char === '[') return "while(m[p]) {\n";
        if (char === ']') return "}\n";
        if (char === '.') return "self.postMessage({ type: 'OUTPUT', value: mem[p] });\n";
        if (char === ',') {
            return `
                self.postMessage({ type: 'NEED_INPUT' });
                
                // 2. Засыпаем, пока sharedArray[0] равен 0
                // Поток застынет на этой строчке!
                Atomics.wait(sharedArray, 0, 0); 
                
                // 3. Проснулись! Забираем данные из второй ячейки
                mem[p] = sharedArray[1];
                
                // 4. Сбрасываем статус обратно в 0 для следующего ввода
                sharedArray[0] = 0; 
            \n`;
        }
        return '';
    }

    _formatJs(rawJs: string, indentSpaces: number = 2): string {
        const lines = rawJs
            .split('\n')
            .map(line => line.trim())
            .filter(line => line.length > 0);

        let indentLevel = 0;
        const formattedLines: string[] = [];

        for (const line of lines) {
            if (line.startsWith('}')) {
                indentLevel = Math.max(0, indentLevel - 1);
            }

            const currentIndent = ' '.repeat(indentLevel * indentSpaces);
            formattedLines.push(currentIndent + line);

            if (line.endsWith('{')) {
                indentLevel++;
            }
        }

        return formattedLines.join('\n');
    }
}