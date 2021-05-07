import bootstrap, { Tooltip } from "bootstrap";

/**
 * Create a tooltip.
 * @param   element Element
 * @returns Tooltip
 */
export function CreateTooltip(element: Element): Tooltip
{
    let bsTooltip = new Tooltip(element);

    element.addEventListener("inserted.bs.tooltip", () => {

        let tooltipId: string = <string>element.getAttribute("aria-describedby");
        if (!tooltipId) {
            return;
        }

        let tooltip: Element = <Element>document.querySelector(`#${tooltipId}`);
        if (!tooltip) {
            return;
        }

        element.classList.forEach((c: string, i: number) => {

            if (!c.match(/^tooltip-/i)) {
                return;
            }

            tooltip.classList.add(c);

        });

    });

    return bsTooltip;
}

/**
 * Create tooltips.
 * @param   elements NodeListOf<Element>
 * @returns void
 */
export function CreateTooltips(elements: NodeListOf<Element>): void
{
    elements.forEach((element: Element, i: number) => {
        CreateTooltip(element);
    });
}

/**
 * Create all tooltips immediately.
 */
CreateTooltips(document.querySelectorAll("*[data-toggle=\"tooltip\"]"));
