import { getEl } from './dom';

export interface ModalInstance {
    show: () => void;
    hide: () => void;
    isOpen: () => boolean;
}

export function createModal(modalId: string): ModalInstance {
    const modal = getEl<HTMLDivElement>(modalId);

    const closeOnEscape = (e: KeyboardEvent): void => {
        if (e.key === 'Escape' && isOpen()) hide();
    };

    const closeOnBackdrop = (e: MouseEvent): void => {
        if (e.target === modal) hide();
    };

    function show(): void {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.addEventListener('keydown', closeOnEscape);
        modal.addEventListener('click', closeOnBackdrop);
    }

    function hide(): void {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.removeEventListener('keydown', closeOnEscape);
        modal.removeEventListener('click', closeOnBackdrop);
    }

    function isOpen(): boolean {
        return !modal.classList.contains('hidden');
    }

    return { show, hide, isOpen };
}

export function bindModalTriggers(modalId: string, openId: string, closeId: string): void {
    const modal = createModal(modalId);
    const openBtn = document.getElementById(openId);
    const closeBtn = document.getElementById(closeId);

    openBtn?.addEventListener('click', () => modal.show());
    closeBtn?.addEventListener('click', () => modal.hide());
}
