import bootstrap, { Modal } from "bootstrap";
import { ButtonType } from "./button";

/**
 * Enumeration for modal size.
 */
export enum ModalSize
{
    ExtraSmall  = "xs",
    Small       = "sm",
    Medium      = "md",
    Large       = "lg",
    ExtraLarge  = "xl",
}

/**
 * Cast a string to a ModalSize.
 * @param   string|null s
 * @returns ModalSize
 */
export function StringToModalSize(s: string|null): ModalSize
{
    if (null === s) {
        return ModalSize.Medium;
    }

    switch (s) {
        case "xs":
        case "sm":
        case "md":
        case "lg":
        case "xl":
            return <ModalSize>s;
    }

    throw "String invalid.";
}

/**
 * Enumeration for modal type.
 */
export enum ModalType
{
    Primary     = "primary",
    Secondary   = "secondary",
    Tertiary    = "tertiary",
    Inverse     = "inverse",
    Info        = "info",
    Success     = "success",
    Warning     = "warning",
    Danger      = "danger",
    Grey        = "grey",
}

/**
 * Cast a string to a ModalType.
 * @param   string|null s
 * @returns ModalType
 */
export function StringToModalType(s: string|null): ModalType
{
    if (null === ModalType) {
        return ModalType.Primary;
    }

    switch (s) {
        case "primary":
        case "secondary":
        case "tertiary":
        case "inverse":
        case "info":
        case "success":
        case "warning":
        case "danger":
            return <ModalType>s;

        case "error":
            return ModalType.Danger; // Convert "error" to Bootstrap
    }

    throw "String invalid.";
}

/**
 * Interface for params to create a customisable modal.
 */
export interface CreateModalInterface
{
    readonly body:          string;
    readonly bodyScroll?:   boolean;
    readonly blocking?:     boolean;
    readonly title?:        string;
    readonly size?:         ModalSize;
    readonly type?:         ModalType;
    readonly noClose?:      boolean;
    readonly buttons?:      Array<CreateModalButtonInterface>;
    readonly onShow?:       (event: Event, modal: Element, bsModal: Modal) => boolean;
    readonly onShown?:      (event: Event, modal: Element, bsModal: Modal) => boolean;
    readonly onDismiss?:    (event: Event, modal: Element, bsModal: Modal) => boolean;
    readonly onDismissed?:  (event: Event, modal: Element, bsModal: Modal) => boolean;
    options?:               any;
}

/**
 * Interface for params to create a button within a customisable modal.
 */
export interface CreateModalButtonInterface
{
    readonly text:          string;
    readonly type?:         ButtonType;
    readonly href?:         string;
    readonly onClick?:      (event: MouseEvent, button: Element, modal: Element, bsModal: Modal) => boolean;
}

/**
 * Create a customisable modal.
 * @param  params CreateModalInterface
 * @return Modal
 */
export function CreateModal(params: CreateModalInterface): Modal
{
    if (typeof params.options !== "object" || null === params.options) {
        params.options = {};
    }

    // Create modal
    let modal = document.createElement("div");
    let bsModal: Modal;
    modal.setAttribute("tabindex", "-1");
    modal.classList.add("modal", "fade");

    if (params.blocking) {
        modal.setAttribute("data-bs-backdrop", "static");
        params.options.backdrop = "static";

        modal.setAttribute("data-bs-keyboard", "false");
        params.options.keyboard = false;
    }

    // Create modal dialog
    let modalDialog = document.createElement("div");
    modal.appendChild(modalDialog);
    modalDialog.classList.add("modal-dialog", "modal-dialog-centered");

    if (params.bodyScroll) {
        modalDialog.classList.add("modal-dialog-scrollable");
    }

    if (params.size) {
        modalDialog.classList.add(`modal-${params.size}`);
    }

    if (params.type) {
        modalDialog.classList.add(`modal-${params.type}`);
    }

    // Create modal content
    let modalContent = document.createElement("div");
    modalDialog.appendChild(modalContent);
    modalContent.classList.add("modal-content");

    // Create modal header
    if (params.title || !params.noClose) {
        let modalTitle = document.createElement("div");
        modalTitle.classList.add("modal-header");
        modalContent.appendChild(modalTitle);

        if (params.title) {
            let modalTitleText = document.createElement("h5");
            modalTitleText.classList.add("modal-title");
            modalTitleText.textContent = params.title;
            modalTitle.appendChild(modalTitleText);
        }

        if (!params.noClose) {
            let modalTitleClose = document.createElement("button");
            modalTitleClose.setAttribute("type", "button");
            modalTitleClose.classList.add("btn-close");
            modalTitleClose.setAttribute("data-bs-dismiss", "modal");
            modalTitleClose.setAttribute("aria-label", "Close");
            modalTitle.appendChild(modalTitleClose);
        }
    }

    // Create modal body
    let modalBody = document.createElement("div");
    modalBody.classList.add("modal-body");
    modalBody.innerHTML = params.body;
    modalContent.appendChild(modalBody);

    // Create modal footer
    if (params.buttons) {
        let modalFooter = document.createElement("div");
        modalFooter.classList.add("modal-footer");
        modalContent.appendChild(modalFooter);

        params.buttons.forEach((b: CreateModalButtonInterface, i: number) => {

            let button = document.createElement("a");

            button.text = b.text;

            button.classList.add("btn");
            if (b.type) {
                button.classList.add(b.type);
            }

            button.href = b.href ? b.href : "#";

            button.addEventListener("click", (event: MouseEvent) => {
                return b.onClick ? b.onClick(event, button, modal, bsModal) : undefined;
            });

            modalFooter.appendChild(button);

        });
    }

    // Append modal to body and show it
    document.body.appendChild(modal);

    bsModal = new Modal(modal, params.options);

    // Events
    modal.addEventListener("show.bs.modal", (event: Event) => {
        return params.onShow ? params.onShow(event, modal, bsModal) : undefined;
    });

    modal.addEventListener("shown.bs.modal", (event: Event) => {
        return params.onShown ? params.onShown(event, modal, bsModal) : undefined;
    });

    modal.addEventListener("hide.bs.modal", (event: Event) => {
        return params.onDismiss ? params.onDismiss(event, modal, bsModal) : undefined;
    });

    modal.addEventListener("hidden.bs.modal", (event: Event) => {
        return params.onDismissed ? params.onDismissed(event, modal, bsModal) : undefined;
    });

    bsModal.show();

    return bsModal;
}
