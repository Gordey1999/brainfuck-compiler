
export class TabManager {

	constructor(element, controller, editor, input) {
		this._el = element;
		this._controller = controller;
		this._editor = editor;
		this._input = input;
		this._tabData = [];

		this._bind();
		this._addTab();
	}

	_bind() {
		this._el.querySelector('.tab-plus').addEventListener('click', this._addTab.bind(this));

		setInterval(this._setTitle.bind(this), 5000);
	}

	_setTitle() {
		const activeTab = this._el.querySelector('.--active');
		if (!activeTab) { return; }

		const code = this._editor.getCode();
		const match = code.match(/^#\s*title:\s*([\wА-Яа-я ]+)/);
		if (match) {
			activeTab.querySelector('.tab-name').textContent = match[1];
		}
	}

	_addTab() {
		const el = document.createElement('div');
		const name = document.createElement('span');
		const close = document.createElement('span');

		el.classList.add('tab');
		name.classList.add('tab-name');
		close.classList.add('tab-close');

		name.textContent = 'untitled';
		close.textContent = ' x';

		el.appendChild(name);
		el.appendChild(close);
		this._el.lastElementChild.before(el);

		this._tabData.push({
			el: el,
			code: '',
			input: '',
			inputActive: false,
		})

		el.addEventListener('click', this._setActiveTab.bind(this, el));
		close.addEventListener('click', this._closeTab.bind(this, el));

		this._setActiveTab(el);

	}

	_setActiveTab(el) {
		const activeTab = this._el.querySelector('.--active');
		if (activeTab === el) { return; }

		if (activeTab) {
			activeTab.classList.remove('--active');

			const tabData = this._getTabData(activeTab);
			tabData.code = this._editor.getCode();
			tabData.input = this._input.getRaw();
			tabData.inputActive = this._input.isActive();
		}

		this._controller.onStop();

		const tabData = this._getTabData(el);
		this._editor.setCode(tabData.code);
		this._input.set(tabData.input);
		this._input.setActive(tabData.inputActive);

		el.classList.add('--active');
	}

	_closeTab(el, e) {
		e.stopPropagation();
		if (this._el.querySelectorAll('.tab').length <= 2) { return; }

		const activeTab = this._el.querySelector('.--active');
		if (activeTab === el) {
			if (el.previousElementSibling) {
				this._setActiveTab(el.previousElementSibling);
			} else if(el.nextElementSibling) {
				this._setActiveTab(el.nextElementSibling);
			}
		}

		this._removeTabData(el);
		el.remove();
	}

	_getTabData(el) {
		for (const tab of this._tabData) {
			if (tab.el === el) { return tab; }
		}
		return null;
	}

	_removeTabData(el) {
		for (const i in this._tabData) {
			if (this._tabData[i].el === el) {
				this._tabData.splice(i, 1);
			}
		}
	}
}