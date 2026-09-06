import {MetaParser} from "./lib/MetaParser";
import {SaveState} from './types';
import type {Controller} from "./Controller";
import type {Editor, SerializedStateData} from "./Editor";
import type {FileInput} from "./FileInput";

interface TabData {
    el: HTMLElement,
    tabId: number,
    input: string,
    inputActive: boolean,
    language: 'bf' | 'bfx',
    isSubtab: boolean,
}

export class TabManager {
    private _tabIdCounter: number = 0;
    private _fillTitleTimeout: number | null = null;

    constructor(
        private _el: HTMLElement,
        private _controller: Controller,
        private _editor: Editor,
        private _input: FileInput,
        private _tabData: TabData[] = [],
    ) {
		this._tabData = [];

		this._bind();
		this._init();
	}

	public showCompiled(code: string): void {
		const parent = this._getActiveTab()!;

		this._updateActiveTabData();
		const tabData = this._getTabData(parent);

		const children = this._getChildTabs(parent);

		if (children.length > 0) {
			this._closeTab(children[0]);
		}

		this._addTab(true, parent, code, tabData.input);
	}

	public async getStateForSave(): Promise<SaveState> {
		this._updateActiveTabData();

		return this._tabData
			.filter((tab) => !tab.isSubtab)
			.map((tab) => {
				return {
					code: this._editor.getStateCode(String(tab.tabId)),
					input: tab.input,
					language: tab.language,
					isSubtab: tab.isSubtab,
				};
			});
	}

	public async setStateFromSave(data: SaveState): Promise<void> {
		this._closeAll();

		let active = null;
		let lastParent = null;
		for (const tab of data) {

			const tabData = this._createTab(
				tab.language,
				tab.isSubtab ? lastParent : null,
				tab.code,
				tab.input,
				tab?.editor ?? null
			);

			if (!tab.isSubtab) {
				lastParent = tabData.el;
			}

			if (tab?.active) {
				active = tabData;
			}
		}

		this._setActiveTab(active ? active.el : this._tabData[0].el);
	}

	public async getFullState(): Promise<SaveState> {
		this._updateActiveTabData();

		return this._tabData.map((tab) => {
			return {
				code: this._editor.getStateCode(String(tab.tabId)),
				input: tab.input,
				language: tab.language,
				isSubtab: tab.isSubtab,
				editor: this._editor.getSerializableState(String(tab.tabId)),
				active: tab.el === this._getActiveTab(),
			};
		});
	}

	public async setFullState(state: SaveState): Promise<void> {
		await this.setStateFromSave(state);
	}

	private onAddTab(language: 'bf' | 'bfx'): void {
		const title = this.getTitle('', language);
		const code = `# @title: ${title}\n\n`;
		this._addTab(language === 'bf', null, code, '');
	}

	public onEditorChange = (): void => {
		if (this._fillTitleTimeout) {
			clearTimeout(this._fillTitleTimeout);
		}

		this._fillTitleTimeout = setTimeout(this._setTitle.bind(this), 1000);
	}

	private _init(): void {
		const code = "# title: Hello\n\n out 'Hello, World!'";
		this._addTab(false, null, code);
	}

	private _bind(): void {
		this._el.querySelector('.tab-plus')!
			.addEventListener('click', this.onAddTab.bind(this, 'bfx'));
		this._el.querySelector('.tab-plus-bf')!
			.addEventListener('click', this.onAddTab.bind(this, 'bf'));
	}

	private _setTitle(): void {
		const activeTab = this._getActiveTab();
		if (!activeTab) { return; }

        const tabData = this._getTabData(activeTab);

		const code = this._editor.getCode();
		activeTab.querySelector('.tab-name')!.textContent = this.getTitle(code, tabData.language);
	}

	public getTitle(code: string, language: 'bf' | 'bfx') {
		const defaultName = language === 'bf' ? 'untitled.bf' : 'untitled';
		return MetaParser.getHeaderValue(code, 'title', defaultName);
	}

	private _createTab(
        language: 'bf' | 'bfx',
        parent: HTMLElement | null = null,
        code: string = '',
        input: string = '',
        editor: SerializedStateData | null = null
    ): TabData {
		const el = document.createElement('div');
		const name = document.createElement('span');
		const close = document.createElement('span');

		el.classList.add('tab');
		name.classList.add('tab-name');
		close.classList.add('tab-close');

		name.textContent = this.getTitle(code, language);
		close.textContent = 'x';

		if (language === 'bf') {
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
			this._el.querySelector('.tab-plus')!.before(el);
		}

		const tabId = this._tabIdCounter++;
		if (editor) {
			this._editor.setSerializableState(String(tabId), language, code, editor);
		} else {
			this._editor.addState(String(tabId), code, language);
		}

		const tab = {
			el: el,
			tabId: tabId,
			input: input,
			inputActive: input.length > 0,
			language: language,
			isSubtab: !!parent,
		};

		if (parent) {
			const parentIndex = this._tabData.indexOf(this._getTabData(parent));
			this._tabData.splice(parentIndex + 1, 0, tab);
		} else {
			this._tabData.push(tab);
		}

		el.addEventListener('click', this._setActiveTab.bind(this, el));
		close.addEventListener('click', this._closeTab.bind(this, el));

		return tab;
	}

	private _addTab(bf: boolean = false, parent: HTMLElement | null = null, code = '', input = '') {
		const tab = this._createTab(bf ? 'bf' : 'bfx', parent, code, input);
		this._setActiveTab(tab.el);
	}

	private _setActiveTab(el: HTMLElement): void {
		const activeTab = this._getActiveTab();
		if (activeTab === el) { return; }

		this._setTitle();
		this._updateActiveTabData();
		activeTab?.classList.remove('--active');
		this._controller.onStop();

		const tabData = this._getTabData(el);
		this._setButtons(tabData.language);
		this._editor.switchState(String(tabData.tabId));
		this._input.set(tabData.input);
		this._input.setActive(tabData.inputActive);

		el.classList.add('--active');
	}

	private _updateActiveTabData(): void {
		const activeTab = this._getActiveTab();

		if (activeTab) {
			const tabData = this._getTabData(activeTab);
			tabData.input = this._input.getRaw();
			tabData.inputActive = this._input.isActive();
		}
	}

	private _getChildTabs(el: HTMLElement): HTMLElement[] {
		const result = [];
		let last = el;
		while (true) {
			const tab = last.nextElementSibling as HTMLElement;
			if (!tab.classList.contains('tab-subtab')) {
				break;
			}
			result.push(tab);
			last = tab;
		}

		return result;
	}

	private _getActiveTab(): HTMLElement | null {
		return this._el.querySelector('.tab.--active');
	}

	private _setButtons(language: 'bf' | 'bfx'): void {
		if (language === 'bf') {
			document.querySelector('.buttons-bf')!.classList.add('--active');
			document.querySelector('.buttons-bb')!.classList.remove('--active');
		} else {
			document.querySelector('.buttons-bb')!.classList.add('--active');
			document.querySelector('.buttons-bf')!.classList.remove('--active');
		}
	}

	private _closeTab(el: HTMLElement, e: MouseEvent | null = null): void {
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
				this._setActiveTab(el.previousElementSibling as HTMLElement);
			} else if(el.nextElementSibling) {
				this._setActiveTab(el.nextElementSibling as HTMLElement);
			}
		}

		this._removeTabData(el);
		el.remove();
	}

	private _getTabData(el: HTMLElement): TabData {
		for (const tab of this._tabData) {
			if (tab.el === el) { return tab; }
		}
		throw new Error(`Tab ${el} not found`);
	}

	private _removeTabData(el: HTMLElement): void {
        this._tabData.forEach((_: TabData, i: number) => {
			if (this._tabData[i].el === el) {
				this._editor.removeState(this._tabData[i].tabId);
				this._tabData.splice(i, 1);
			}
		})
	}

	private _closeAll(): void {
		for (const tab of this._tabData) {
			tab.el.remove();
		}
		this._tabData = [];
		this._editor.clearStates();
	}
}