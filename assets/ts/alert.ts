import Swal, { SweetAlertOptions, SweetAlertIcon, SweetAlertInput, SweetAlertResult } from "sweetalert2";

/**
 * Cast a string to a SweetAlertIcon.
 * @param   string s
 * @returns SweetAlertIcon
 */
export function StringToSweetAlertIcon(s: string): SweetAlertIcon
{
    switch (s) {
        case "info":
        case "success":
        case "warning":
        case "error":
        case "question":
            return s;

        case "danger":
            return "error"; // Convert Bootstrap to Swal
    }

    throw "String invalid.";
}

/**
 * Interface for params to create a fancy alert.
 */
export interface AlertInterface
{
    readonly message:       string;
    readonly title?:        string;
    icon?:                  string;
    readonly buttonOkText?: string;
    readonly callbackOk?:   () => boolean;
    readonly options?:      SweetAlertOptions;
}

/**
 * Create a fancy alert.
 * @param  params AlertInterface
 * @return Promise<SweetAlertResult>
 */
export function Alert(params: AlertInterface): Promise<SweetAlertResult>
{
    if (!params.message.length) {
        throw "Message not specified.";
    }

    if (!params.icon) {
        params.icon = "info";
    }

    return Swal.fire({
        title:  params.title,
        text:   params.message,
        icon:   StringToSweetAlertIcon(params.icon),
        ...params.options,
    });
}

/**
 * Interface for params to create a fancy confirmation.
 */
export interface ConfirmInterface
{
    readonly message:           string;
    readonly title?:            string;
    icon?:                      string;
    readonly buttonOkText?:     string;
    readonly buttonCancelText?: string;
    readonly callbackOk?:       () => boolean;
    readonly callbackCancel?:   () => boolean;
    readonly options?:          SweetAlertOptions;
}

/**
 * Create a fancy confirmation.
 * @param  params ConfirmInterface
 * @return Promise<SweetAlertResult>
 */
export function Confirm(params: ConfirmInterface)
{
    if (!params.message.length) {
        throw "Message not specified.";
    }

    if (!params.icon) {
        params.icon = "question";
    }

    return Swal.fire({
        title:  params.title,
        text:   params.message,
        icon:   StringToSweetAlertIcon(params.icon),
        ...params.options,
    });

}

/**
 * Interface for params to create a fancy prompt.
 */
export interface PromptInterface
{
    readonly message:           string;
    readonly title?:            string;
    icon?:                      string;
    readonly buttonOkText?:     string;
    readonly buttonCancelText?: string;
    readonly callbackOk?:       () => boolean;
    readonly callbackCancel?:   () => boolean;
    readonly mandatory?:        boolean;
    readonly placeholder?:      string;
    readonly valueType?:        SweetAlertInput;
    readonly value?:            any;
    readonly options?:          SweetAlertOptions;
}

/**
 * Create a fancy prompt.
 * @param  params ConfirmInterface
 * @return Promise<SweetAlertResult>
 */
export function Prompt(params: PromptInterface): Promise<SweetAlertResult>
{
    if (!params.message.length) {
        throw "Message not specified.";
    }

    if (!params.icon) {
        params.icon = "question";
    }

    return Swal.fire({
        title:  params.title,
        text:   params.message,
        icon:   StringToSweetAlertIcon(params.icon),
        ...params.options,
    });
}

/**
 * Interface for error to create an error message.
 */
export interface ErrorMessageInterface
{
    message:            string;
    icon?:              SweetAlertIcon;
    readonly noAlert?:  boolean;
    prefix?:            string;
}

/**
 * Create an error message.
 * @param  error ErrorMessageInterface
 * @return void
 */
export function ErrorMessage(error: ErrorMessageInterface): void
{
    if (!error.message || !(error.message = error.message.trim()).length) {
        error.message = "Unknown error.";
    }

    if (!error.icon) {
        error.icon = "error";
    }

    if (!error.prefix || !(error.prefix = error.prefix.trim()).length) {
        error.prefix = "Error";
    }

    console.error(`${error.prefix}: ${error.message}`);
    if (!error.noAlert) {
        Alert({
            message:    error.message,
            title:      error.prefix,
            icon:       StringToSweetAlertIcon(error.icon),
        });
    }
}
