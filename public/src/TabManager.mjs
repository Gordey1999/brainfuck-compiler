
export class TabManager {

	constructor(element, controller, builder, editor, input) {
		this._el = element;
		this._controller = controller;
		this._builder = builder;
		this._editor = editor;
		this._input = input;
		this._tabData = [];

		this._bind();
		this._init();
	}

	showCompiled(code) {
		const parent = this._getActiveTab();

		const children = this._getChildTabs(parent);
		//const title = this.getTitle(code);

		if (children.length > 0) {
			this._closeTab(children[0]);
		}
		// todo add min

		this._addTab(true, parent, code);
	}

	_init() {
		this._addTab();
		this._setActiveTab(this._el.firstElementChild);
	}

	_bind() {
		this._el.querySelector('.tab-plus')
			.addEventListener('click', this._addTab.bind(this, false, null, '', ''));
		this._el.querySelector('.tab-plus-bf')
			.addEventListener('click', this._addTab.bind(this, true, null, '', ''));

		setInterval(this._setTitle.bind(this), 5000);
	}

	_setTitle() {
		const activeTab = this._getActiveTab();
		if (!activeTab) { return; }

		const code = this._editor.getCode();
		let title = this.getTitle(code);
		if (title === '') {
			title = 'untitled';
		}
		activeTab.querySelector('.tab-name').textContent = title;
	}

	getTitle(code) {
		const match = code.match(/^#\s*title:\s*([\wА-Яа-я .]+)/);
		return match ? match[1] : '';
	}

	_addTab(bf = false, parent = null, code = '', input = '') {
		const el = document.createElement('div');
		const name = document.createElement('span');
		const close = document.createElement('span');

		el.classList.add('tab');
		name.classList.add('tab-name');
		close.classList.add('tab-close');

		let title = this.getTitle(code);
		if (title === '') {
			if (bf) {
				title = 'untitled.bf';
				code = '# title: untitled.bf\n\n' + code;
			} else {
				title = 'untitled';
				code = '# title: untitled\n\n' + code;
			}
		}

		name.textContent = title;
		close.textContent = ' x';

		if (bf) {
			el.classList.add('tab-bf');
		}
		if (parent) {
			el.classList.add('tab-subtab');
		}

		el.appendChild(name);
		el.appendChild(close);

		if (parent) {
			parent.after(el);
		} else {
			this._el.querySelector('.tab-plus').before(el);
		}

		this._tabData.push({
			el: el,
			code: code,
			input: input,
			inputActive: input.length > 0,
			language: bf ? 'bf' : 'bb',
		})

		el.addEventListener('click', this._setActiveTab.bind(this, el));
		close.addEventListener('click', this._closeTab.bind(this, el));

		this._setActiveTab(el);

	}

	_setActiveTab(el) {
		const activeTab = this._getActiveTab();
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
		this._setButtons(tabData.language);
		this._editor.setLanguage(tabData.language);
		this._editor.setCode(tabData.code);
		this._input.set(tabData.input);
		this._input.setActive(tabData.inputActive);

		el.classList.add('--active');
	}

	_getChildTabs(el) {
		const result = [];
		let last = el;
		while (true) {
			const tab = last.nextElementSibling;
			if (!tab.classList.contains('tab-subtab')) {
				break;
			}
			result.push(tab);
			last = tab;
		}

		return result;
	}

	_getActiveTab() {
		return this._el.querySelector('.tab.--active');
	}

	_setButtons(language) {
		if (language === 'bf') {
			document.querySelector('.buttons-bf').classList.add('--active');
			document.querySelector('.buttons-bb').classList.remove('--active');
		} else {
			document.querySelector('.buttons-bb').classList.add('--active');
			document.querySelector('.buttons-bf').classList.remove('--active');
		}
	}

	_closeTab(el, e) {
		e?.stopPropagation();

		const children = this._getChildTabs(el);
		if (children.length > 0) {
			for (const child of children) {
				this._closeTab(child);
			}
		}

		if (this._el.querySelectorAll('.tab').length <= 3) { return; }

		const activeTab = this._getActiveTab();
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