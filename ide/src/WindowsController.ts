

export class WindowsController {
    private _activeResizer: HTMLElement | null = null;
    private _startPos: number = 0;
    private _firstSize: number = 0;
    private _currentPercent: number = 0;

    constructor() {
		this._bind();
		this._loadSizes();
	}

    private _bind(): void {
		document.querySelectorAll('.resizer').forEach((el) => {
			el.addEventListener('mousedown', this._onMouseDown as EventListener);
		})

		document.addEventListener('mousemove', this._onMouseMove);
		document.addEventListener('mouseup', this._onMouseUp);
	}

    private _onMouseDown = (e: MouseEvent): void => {
        this._activeResizer = e.currentTarget as HTMLElement;

        const previous = this._activeResizer.previousElementSibling as HTMLElement | null;
        if (!previous) return;

        if (this._isHorizontal()) {
            this._startPos = e.clientY;
            this._firstSize = previous.getBoundingClientRect().height;
        } else {
            this._startPos = e.clientX;
            this._firstSize = previous.getBoundingClientRect().width;
        }

		this._startDrag();
	}

    private _onMouseMove = (e: MouseEvent): void => {
		if (this._activeResizer === null) { return; }

        const previous = this._activeResizer.previousElementSibling as HTMLElement | null;
        const parent = this._activeResizer.parentNode as HTMLElement | null;

        if (!previous || !parent) return;


        if (this._isHorizontal()) {
            const dy = e.clientY - this._startPos;
            const containerHeight = parent.getBoundingClientRect().height;
            this._currentPercent = ((this._firstSize + dy) / containerHeight) * 100;

            previous.style.height = `${this._currentPercent}%`;
        } else {
            const dx = e.clientX - this._startPos;
            const containerWidth = parent.getBoundingClientRect().width;
            this._currentPercent = ((this._firstSize + dx) / containerWidth) * 100;

            previous.style.width = `${this._currentPercent}%`;
        }
	}

    private _onMouseUp = (e: MouseEvent): void => {
		if (this._activeResizer === null) { return; }

		if (this._currentPercent !== null) {
			this._saveSize(this._activeResizer.id, this._currentPercent);
		}

		this._stopDrag();
		this._activeResizer = null;
	}

    private _startDrag(): void {
        if (!this._activeResizer) return;
		this._activeResizer.classList.add('--active');
		document.body.style.cursor = 'grabbing';
		document.body.style.userSelect = 'none';
	}

    private _stopDrag(): void {
        if (!this._activeResizer) return;
		this._activeResizer.classList.remove('--active');
		document.body.style.removeProperty('cursor');
		document.body.style.removeProperty('user-select');
	}

    private _isHorizontal(): boolean {
        if (!this._activeResizer) return false;
		return this._activeResizer.classList.contains('--horizontal');
	}

    private _saveSize(id: string, percent: number): void {
        if (!id) { return; }
		localStorage.setItem(`window-size-${id}`, String(percent));
	}

    private _loadSizes(): void {
		document.querySelectorAll('.resizer').forEach((node) => {
            const el = node as HTMLElement;
			if (!el.id) return;

			const savedPercent = localStorage.getItem(`window-size-${el.id}`);
            const target = el.previousElementSibling as HTMLElement | null;

            if (savedPercent && target) {
                if (el.classList.contains('--horizontal')) {
                    target.style.height = `${savedPercent}%`;
                } else {
                    target.style.width = `${savedPercent}%`;
                }
            }
		});
	}
}