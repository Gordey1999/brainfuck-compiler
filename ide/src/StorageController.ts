import {SampleStorage} from "./lib/SampleStorage";
import {Storage} from "./lib/Storage";
import type {TabManager} from "./TabManager";
import {SaveState} from "./types";

export class StorageController {
    private _autoSaveTimeout: number|null = null;

    constructor(
        private _saveModal: HTMLElement,
        private _loadModal: HTMLElement,
        private _storage: Storage,
        private _tabManager: TabManager
    ) {
		this._bind();
		this._initSession();
	}

	private _bind(): void {
		this._saveModal.querySelector('.modal-header-close .link')!.addEventListener('click', this.onClose);
		this._loadModal.querySelector('.modal-header-close .link')!.addEventListener('click', this.onClose);
		this._saveModal.addEventListener('click', this.onModalClick.bind(this, this._saveModal));
		this._loadModal.addEventListener('click', this.onModalClick.bind(this, this._loadModal));

		this._saveModal.querySelector('.link-new-slot')!.addEventListener('click', this.onNewSlot);
		this._saveModal.querySelector('.link-export')!.addEventListener('click', this.onExport);
		this._saveModal.querySelector('.link-download')!.addEventListener('click', this.onDownload);
		this._loadModal.querySelector('.link-import')!.addEventListener('click', this.onImport);

		window.addEventListener('beforeunload', this.onBeforeUnload);
	}

	private async _initSession(): Promise<void> {
		const lastSession = await this._storage.loadCurrentSession();

		if (lastSession) {
			await this._tabManager.setFullState(lastSession);
		} else {
            await this._tabManager.setStateFromSave(await SampleStorage.loadHomePage());
		}
	}

	public onSave = (): void => {
		this._saveModal.classList.add('--active');
		this._renderSaveSlots();
	}

    public onLoad = (): void => {
		this._loadModal.classList.add('--active');
		this._renderLoadSlots();
		this._renderExamples();
	}

	public onClose = (): void => {
		this._saveModal.classList.remove('--active');
		this._loadModal.classList.remove('--active');
	}

	private onModalClick = (modal: HTMLElement, e: MouseEvent): void => {
		if (e.currentTarget === modal) {
			this.onClose();
		}
	}

	private onNewSlot = async (): Promise<void> => {
		const userInput = prompt("input new slot name:", "new slot");

		if (userInput !== null && userInput.trim() !== "") {
			const id = await this._storage.addSlot(userInput.trim());
			const data = await this._tabManager.getStateForSave();
			await this._storage.save(id, data);
			await this._renderSaveSlots();
		}
	}

	private onSlotSave = async (slotId: number): Promise<void> => {
		const isConfirmed = confirm('Are you sure you want to overwrite this save?');

		if (isConfirmed) {
			const data = await this._tabManager.getStateForSave();
			await this._storage.save(slotId, data);
			await this._renderSaveSlots();
		}
	}

	private onSlotLoad = async (slotId: number): Promise<void> => {
		const isConfirmed = confirm('Are you sure you want to close current project?');

		if (isConfirmed) {
			const data = await this._storage.load(slotId);
            if (data) {
                await this._tabManager.setStateFromSave(data);
            }
			this.onClose();
		}
	}

	private onSlotRename = async (slotId: number, oldName: string): Promise<void> => {
		const userInput = prompt("input new slot name:", oldName);

		if (userInput !== null && userInput.trim() !== "") {
			await this._storage.renameSlot(slotId, userInput.trim());
			await this._renderSaveSlots();
			await this._renderLoadSlots();
		}
	}

	private onSlotDelete = async (slotId: number): Promise<void> => {
		const isConfirmed = confirm('Are you sure you want to delete this save?');

		if (isConfirmed) {
			await this._storage.deleteSlot(slotId);
			await this._renderSaveSlots();
			await this._renderLoadSlots();
		}
	}

    private onSampleLoad = async (id: number): Promise<void> => {
		const isConfirmed = confirm('Are you sure you want to close current project?');

		if (isConfirmed) {
			const data = await SampleStorage.load(id);
			await this._tabManager.setStateFromSave(data);
			this.onClose();
		}
	}

    private onExport = async (): Promise<void> => {
		const data = await this._tabManager.getStateForSave();

		if ('showSaveFilePicker' in window) {
			await this._exportWithPicker(data);
		} else {
			alert('Your browser doesn\'t support file picker! Use download button.');
		}
	}

    private onDownload = async (): Promise<void> => {
		const data = await this._tabManager.getStateForSave();
		await this._exportWithBlob(data);
	}

    private onImport = async (): Promise<void> => {
		let loadedData;

		if ('showOpenFilePicker' in window) {
			loadedData = await this._importWithPicker();
		} else {
			loadedData = await this._importWithInput();
		}

		if (loadedData) {
			try {
				await this._tabManager.setStateFromSave(loadedData);
				this.onClose();
			} catch (err) {
				alert('Error importing project.');
				console.error(err);
			}
		}
	}

	public onEditorChange = (): void => {
		if (this._autoSaveTimeout) {
			clearTimeout(this._autoSaveTimeout);
		}

		this._autoSaveTimeout = setTimeout(async () => {
			const data = await this._tabManager.getFullState();
			await this._storage.saveCurrentSession(data);
		}, 10000);
	}

	private onBeforeUnload = async (): Promise<void> => {
		const data = await this._tabManager.getFullState();
		await this._storage.saveCurrentSession(data);
	}

	private async _exportWithPicker(data: SaveState): Promise<void> {
		const jsonString = JSON.stringify(data, null, 2);

		try {
			const handle = await window.showSaveFilePicker({
				suggestedName: 'project.bfp',
				types: [{
					description: 'Brainfuck Project File (.bfp)',
					accept: {
						'application/json': ['.bfp']
					}
				}]
			});

			const writable = await handle.createWritable();
			await writable.write(jsonString);
			await writable.close();

			alert('Saved successfully.');
		} catch (err) {
            if (err instanceof Error) {
                if (err.name !== 'AbortError') {
                    console.error('Ошибка при сохранении:', err);
                    alert('Failed to read file.');
                }
            } else {
                console.error('Неизвестная ошибка при сохранении:', err);
            }
		}
	}

	private async _exportWithBlob(data: SaveState): Promise<void> {
		const jsonString = JSON.stringify(data, null, 2);

		const blob = new Blob([jsonString], { type: 'application/json' });
		const url = URL.createObjectURL(blob);
		const a = document.createElement('a');
		a.href = url;
		a.download = 'project.bfp';
		document.body.appendChild(a);
		a.click();
		document.body.removeChild(a);
		URL.revokeObjectURL(url);
	}

    private async _importWithPicker() {
		try {
			const [handle] = await window.showOpenFilePicker({
				types: [{
					description: 'Brainfuck Project File (.bfp)',
					accept: {
						'application/json': ['.bfp']
					}
				}],
				multiple: false
			});

			const file = await handle.getFile();
			const text = await file.text();
			return JSON.parse(text);
		} catch (err) {
            if (err instanceof Error) {
                if (err.name !== 'AbortError') {
                    console.error('Ошибка при импорте:', err);
                    alert('Failed to read file.');
                }
            } else {
                console.error('Неизвестная ошибка при импорте:', err);
            }
            return null;
		}
	}

    private async _importWithInput() {
		return new Promise((resolve) => {
			const input = document.createElement('input');
			input.type = 'file';
			input.accept = '.bfp';

			input.onchange = async (event: Event) => {
                const target = event.target as HTMLInputElement;
				const file = target.files?.[0];
				if (!file) {
					resolve(null);
					return;
				}

				try {
					const text = await file.text();
					resolve(JSON.parse(text));
				} catch (err) {
					alert('Invalid file format.');
					resolve(null);
				}
			};

			input.click();
		});
	}

    private async _renderSaveSlots(): Promise<void> {
		let slots = await this._storage.getSlots();

		const slotsEl = this._saveModal.querySelector('.saves') as HTMLElement;
		slotsEl.innerHTML = '';

		const template = this._saveModal.querySelector('.saves-row-template') as HTMLTemplateElement;

		for (let slot of slots) {
            const row = template.content.cloneNode(true) as DocumentFragment;

            row.querySelector('.saves-row__title')!.textContent = slot.name;
            row.querySelector('.saves-row__time')!.textContent = `(${slot.timeAgo})`;

			row.querySelector('.link-save')!.addEventListener('click', this.onSlotSave.bind(this, slot.id));
			row.querySelector('.link-rename')!.addEventListener('click', this.onSlotRename.bind(this, slot.id, slot.name));
			row.querySelector('.link-delete')!.addEventListener('click', this.onSlotDelete.bind(this, slot.id));

			slotsEl.appendChild(row);
		}
	}

    private async _renderLoadSlots(): Promise<void> {
		let slots = await this._storage.getSlots();

		const slotsEl = this._loadModal.querySelector('.saves') as HTMLElement;
		slotsEl.innerHTML = '';

		if (slots.length === 0)
		{
			const emptyTemplate = this._loadModal.querySelector('.saves-row-empty-template') as HTMLTemplateElement;
			const row = emptyTemplate.content.cloneNode(true);
			slotsEl.appendChild(row);
		}

		const template = this._loadModal.querySelector('.saves-row-template') as HTMLTemplateElement;

		for (let slot of slots) {
			const row = template.content.cloneNode(true) as DocumentFragment;

			row.querySelector('.saves-row__title')!.textContent = slot.name;
			row.querySelector('.saves-row__time')!.textContent = `(${slot.timeAgo})`;

			row.querySelector('.link-load')!.addEventListener('click', this.onSlotLoad.bind(this, slot.id));
			row.querySelector('.link-rename')!.addEventListener('click', this.onSlotRename.bind(this, slot.id, slot.name));
			row.querySelector('.link-delete')!.addEventListener('click', this.onSlotDelete.bind(this, slot.id));

			slotsEl.appendChild(row);
		}
	}

	private async _renderExamples(): Promise<void> {
		let samples = SampleStorage.list();

		const listEl = this._loadModal.querySelector('.examples') as HTMLElement;
		listEl.innerHTML = '';

		const template = this._loadModal.querySelector('.examples-row-template') as HTMLTemplateElement;

        samples.forEach((sampleText, id) => {
            const row = template.content.cloneNode(true) as DocumentFragment;

            row.querySelector('.saves-row__title')!.textContent = sampleText;

            row.querySelector('.link-load')!.addEventListener('click', () => this.onSampleLoad(id));

            listEl.appendChild(row);
        });
	}
}