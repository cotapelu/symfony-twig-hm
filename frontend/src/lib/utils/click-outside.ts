export class ClickOutside {
	private element: HTMLElement;
	private callback: () => void;
	private handleClick: (e: MouseEvent) => void;

	constructor(element: HTMLElement, callback: () => void) {
		this.element = element;
		this.callback = callback;
		this.handleClick = (e: MouseEvent) => {
			if (!this.element.contains(e.target as Node)) {
				this.callback();
			}
		};
		setTimeout(() => {
			document.addEventListener('click', this.handleClick, true);
		}, 0);
	}

	public destroy(): void {
		document.removeEventListener('click', this.handleClick, true);
	}
}
