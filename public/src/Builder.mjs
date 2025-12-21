
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
		this._console.setStatus('building');
		this._build();
	}

	onBuildMin = () => {
		if (this._stopped) { return; }
		this._stopped = true;
		this._console.stop();
		this._console.setStatus('stopped');
		this._editor.highlightPosition(null);
	}

	_build(min = false) {
		const code = this._editor.getCode();
		const title = this._tabManager.getTitle(code);

		this._query(code, title, min)
			.then(data => {
				if (data.status === 'ok') {
					this._render(data.result);
				} else {
					this._showError(data.message, data?.index);
				}
			});
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
			.then(response => {
				if (!response.ok) {
					this._console.showError('ajax error');
				}
				return response.json();
			})
			.catch(error => {
				this._console.showError(error);
			});
	}

	_render(result) {
		this._tabManager.showCompiled(result);
		this._console.setStatus('finished');
	}

	_showError(message, index) {
		this._console.showError(message);
	}

	_renderState() {
		this._editor.highlightPosition(this._translator.getCurrentPosition());
		this._profiler.render(this._translator.getStorage(), this._translator.getPointer());
		this._console.setCommandsCount(this._translator.commandsCount());
	}
}