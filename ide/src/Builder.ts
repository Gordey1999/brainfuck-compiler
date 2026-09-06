import { Editor } from "./Editor";
import { Console } from "./Console";
import { TabManager } from "./TabManager";

interface CompileResponseData {
	status: 'ok' | 'error';
	result: string;
	log: string;
	message?: string;
	position?: {
		start: number;
		length: number;
	};
}

export class Builder {
	private _ajaxUrl: string = '';
	private _uglify: boolean = false;
	private _editor: Editor;
	private _console: Console;
	private _tabManager!: TabManager;

	constructor(editor: Editor, console: Console, ajaxUrl: string) {
		this._editor = editor;
		this._console = console;
		this._ajaxUrl = ajaxUrl;
	}

	public setTabManager(tabManager: TabManager): void {
		this._tabManager = tabManager;
	}

	public onBuild = (): void => {
		this._console.clear();
		this._console.setStatus('building');
		this._build();
	}

	public onBuildMin = (): void => {
		this._console.clear();
		this._console.setStatus('building');
		this._build(true);
	}

	public onUglify = (e: MouseEvent): void => {
        const currentTarget = e.currentTarget as HTMLElement;
        const toggle = currentTarget.querySelector('.btn-toggle');

        if (toggle) {
            const isActive = toggle.classList.contains('--active');
            this._uglify = !isActive;
            toggle.classList.toggle('--active', !isActive);
        }
	}

	private async _build(min: boolean = false): Promise<void> {
		const code = this._editor.getCode();
		const title = this._tabManager.getTitle(code, 'bfx');

		try {
			const response = await this._query(code, title, min, this._uglify);

			if (!response.ok) {
				this._showError('Brainfix compile error:\nSTATUS ' + response.status);
				return;
			}

			const textData = await response.text();

			try {
				const jsonData = JSON.parse(textData) as CompileResponseData;

				if (jsonData.status === 'ok') {
					this._render(jsonData.result, jsonData.log);
				} else {
					this._showError(jsonData.message || 'Unknown compile error', jsonData.position);
				}
			} catch (e) {
				this._render(textData);
				this._showError('cant parse json');
			}

		} catch (error) {
			this._showError("Brainfix compile server is temporary unavailable.\nTry later");
		}
	}

    private _query(code: string, title: string, min: boolean = false, uglify: boolean = false): Promise<Response> {
		return fetch(this._ajaxUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
			},
			body: JSON.stringify({
				title: title,
				code: code,
				min: min,
				uglify: uglify,
			})
		})
	}

    private _render(result: string, log: string = ''): void {
		this._editor.highlightError();
		this._console.echo(log);
		this._tabManager.showCompiled(result);
		this._console.setStatus('finished');
	}

    private _showError(message: string, position?: { start: number; length: number }): void {
		this._console.showError(message);
		if (position) {
			this._editor.highlightError(position.start, position.length);
		}
	}
}