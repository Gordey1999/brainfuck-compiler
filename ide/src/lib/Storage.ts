import localforage from "localforage"
import {SaveState} from "../types";

interface SaveSlot {
    id: number;
    name: string;
    updatedAt: number;
}

type SlotsList = Record<number, SaveSlot>;

export class Storage {
	constructor() {

	}

	async getSlots() {
		let slots = await localforage.getItem<SlotsList>('save_slots') || {};

		return Object.values(slots).map(slot => ({
			id: slot.id,
			name: slot.name,
			updatedAt: slot.updatedAt,
			timeAgo: this._formatRelativeTime(slot.updatedAt)
		})).reverse();
	}

	async addSlot(slotName: string) {
		try {
			const slotsList = await localforage.getItem<SlotsList>('save_slots') || {};

            const keysAsNumbers = Object.keys(slotsList).map(Number);
            const newId = Math.max(0, ...keysAsNumbers) + 1;

			slotsList[newId] = {
				id: newId,
				name: slotName,
				updatedAt: Date.now(),
			};

			await localforage.setItem('save_slots', slotsList);

			return newId;
		} catch (err) {
			alert('Ошибка при сохранении:'+ err);
            throw err;
		}
	}

	async renameSlot(slotId: number, newName: string) {
		try {
			const slotsList = await localforage.getItem<SlotsList>('save_slots') || {};

			slotsList[slotId].name = newName;
			await localforage.setItem('save_slots', slotsList);
		} catch (err) {
			alert('Ошибка при удалении:' + err);
		}
	}

	private async updateSlotTime(slotId: number, updatedAt: number) {
		try {
			const slotsList = await localforage.getItem<SlotsList>('save_slots') || {};

			slotsList[slotId].updatedAt = updatedAt;
			await localforage.setItem('save_slots', slotsList);
		} catch (err) {
			alert('Ошибка при сохранении:' + err);
		}
	}

	async deleteSlot(slotId: number) {
		try {
			const slotsList = await localforage.getItem<SlotsList>('save_slots') || {};

			delete slotsList[slotId];
			await localforage.setItem('save_slots', slotsList);

			await localforage.removeItem(`save_slot_${slotId}`);
		} catch (err) {
			alert('Ошибка при сохранении:'+ err);
		}
	}

	async save(slotId: number, data: SaveState) {
		try {
			await localforage.setItem<SaveState>(`save_slot_${slotId}`, data);

			await this.updateSlotTime(slotId, Date.now());
		} catch (err) {
			alert('Ошибка при сохранении:'+ err);
		}
	}

	async load(slotId: number): Promise<SaveState | null> {
		try {
			return await localforage.getItem<SaveState>(`save_slot_${slotId}`);
		} catch (err) {
			alert('Ошибка при загрузке:'+ err);
            throw err;
		}
	}

	async saveCurrentSession(data: SaveState): Promise<void> {
		try {
			await localforage.setItem('last_session_state', data);
		} catch (err) {
			console.error('Ошибка автосохранения сессии:', err);
		}
	}

	async loadCurrentSession(): Promise<SaveState | null> {
		try {
			return await localforage.getItem<SaveState>('last_session_state') || null;
		} catch (err) {
			console.error('Ошибка загрузки сессии:', err);
			return null;
		}
	}

	private _formatRelativeTime(timestamp: number) {
		if (!timestamp) return 'never';

		const msPerMinute = 60 * 1000;
		const msPerHour = msPerMinute * 60;
		const msPerDay = msPerHour * 24;

		const elapsed = timestamp - Date.now();
		const absElapsed = Math.abs(elapsed);

		const rtf = new Intl.RelativeTimeFormat('en', { style: 'narrow', numeric: 'always' });

		if (absElapsed < 5000) {
			return 'now'; // Если прошло меньше 5 секунд
		} else if (absElapsed < msPerMinute) {
			return rtf.format(Math.round(elapsed / 1000), 'second'); // "15s ago"
		} else if (absElapsed < msPerHour) {
			return rtf.format(Math.round(elapsed / msPerMinute), 'minute'); // "10m ago"
		} else if (absElapsed < msPerDay) {
			return rtf.format(Math.round(elapsed / msPerHour), 'hour'); // "2h ago"
		} else {
			return rtf.format(Math.round(elapsed / msPerDay), 'day'); // "1d ago"
		}
	}
}