
export class Builder {
	_ajaxUrl = 'ajax/compile.php';

	constructor(editor, console) {
		this._editor = editor;
		this._console = console;
		this._running = false;
	}

	setTabManager(tabManager) {
		this._tabManager = tabManager;
	}

	onBuild = () => {
		this._console.clear();
		this._console.setStatus('building');
		this._build();
	}

	onBuildMin = () => {
		this._console.clear();
		this._console.setStatus('building');
		this._build(true);
	}

	async _build(min = false) {
		const code = this._editor.getCode();
		const title = this._tabManager.getTitle(code);

		try {
			const response = await this._query(code, title, min);

			if (!response.ok) {
				this._console.showError('ajax error');
			}

			const textData = await response.text();

			try {
				const jsonData = JSON.parse(textData);

				if (jsonData.status === 'ok') {
					this._render(jsonData.result, jsonData.log);
				} else {
					this._showError(jsonData.message, jsonData.position);
				}
			} catch (e) {
				this._render(textData);
				this._showError('cant parse json');
			}

		} catch (error) {
			this._showError("Fetch request failed:", error);
		}
	}

	_query(code, title, min = false) {
		return fetch(this._ajaxUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
			},
			body: JSON.stringify({
				title: title,
				code: code,
				min: min,
			})
		})
	}

	_render(result, log) {
		this._console.echo(log);
		this._tabManager.showCompiled(result);
		this._console.setStatus('finished');
	}

	_showError(message, position) {
		this._console.showError(message);
		this._editor.highlightError(position.start, position.length);
	}
}