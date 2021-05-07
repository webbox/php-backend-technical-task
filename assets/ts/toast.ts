import bootstrap, { Toast } from "bootstrap";

/**
 * Create a toast.
 * @param   element Element
 * @returns Toast
 */
export function CreateToast(element: Element): Toast
{
    let bsToast = new Toast(element, {
        autohide: false,
    });
    bsToast.show();
    return bsToast;
}

/**
 * Create toasts.
 * @param   elements NodeListOf<Element>
 * @returns void
 */
export function CreateToasts(elements: NodeListOf<Element>): void
{
    elements.forEach((element: Element, i: number) => {
        CreateToast(element);
    });
}

/**
 * Create all toasts immediately.
 */
CreateToasts(document.querySelectorAll(".toast"));
