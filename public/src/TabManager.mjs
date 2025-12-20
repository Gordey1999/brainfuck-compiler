
export class TabManager {

	constructor(element, controller, editor, input) {
		this._el = element;
		this._controller = controller;
		this._editor = editor;
		this._input = input;
		this._tabData = [];

		this._bind();
		this._init();
	}

	_init() {
		this._addTab(document.querySelector('#page1').textContent);
		// this._addTab(document.querySelector('#page2').textContent);
		// this._addTab(document.querySelector('#page3').textContent);
		// this._addTab(document.querySelector('#page4').textContent);
		// this._addTab(document.querySelector('#page5').textContent, 'Нукрутоже?\n9');
		// this._addTab(document.querySelector('#page6').textContent);
		// this._addTab(document.querySelector('#page7').textContent);
		this._setActiveTab(this._el.firstElementChild);
	}


	_bind() {
		this._el.querySelector('.tab-plus').addEventListener('click', this._addTab.bind(this, '', ''));

		setInterval(this._setTitle.bind(this), 5000);
	}

	_setTitle() {
		const activeTab = this._el.querySelector('.--active');
		if (!activeTab) { return; }

		const code = this._editor.getCode();
		activeTab.querySelector('.tab-name').textContent = this._getTitle(code);
	}

	_getTitle(code) {
		const match = code.match(/^#\s*title:\s*([\wА-Яа-я ]+)/);
		return match ? match[1] : 'untitled';
	}

	_addTab(code = '', input = '') {
		const el = document.createElement('div');
		const name = document.createElement('span');
		const close = document.createElement('span');

		el.classList.add('tab');
		name.classList.add('tab-name');
		close.classList.add('tab-close');

		name.textContent = this._getTitle(code);
		close.textContent = ' x';

		el.appendChild(name);
		el.appendChild(close);
		this._el.lastElementChild.before(el);

		this._tabData.push({
			el: el,
			code: code,
			input: input,
			inputActive: input.length > 0,
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