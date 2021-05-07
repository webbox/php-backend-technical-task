import { ErrorMessage } from "./alert";

/**
 * Enumeration for AJAX method.
 */
export enum AjaxMethod
{
    Get     = "GET",
    Head    = "HEAD",
    Post    = "POST",
    Patch   = "PATCH",
    Put     = "PUT",
    Delete  = "DELETE",
}

/**
 * Cast a string to a AJAX method.
 * @param   string|null s
 * @returns AjaxMethod
 */
export function StringToAjaxMethod(s: string|null): AjaxMethod
{
    if (null === s) {
        return AjaxMethod.Get;
    }

    switch (s) {
        case "GET":
        case "HEAD":
        case "POST":
        case "PATCH":
        case "PUT":
        case "DELETE":
            return <AjaxMethod>s;
    }

    throw "String invalid.";
}

/**
 * Interface for params to create aan AJAX request.
 */
export interface AjaxRequestInterface
{
    readonly method:        AjaxMethod;
    readonly url:           string;
    readonly payload?:      object;
    readonly contentType?:  string;
    readonly done:          (data: object|string, status: number, xhr: XMLHttpRequest) => boolean;
    readonly fail?:         (error: string, status: number, xhr: XMLHttpRequest) => string;
    readonly always?:       () => any;
}

/**
 * Create an AJAX request.
 * @param request AjaxRequestInterface
 * @returns XMLHttpRequest
 */
export function AjaxRequest(request: AjaxRequestInterface): XMLHttpRequest
{
    if (!request.method) {
        throw "Method not specified.";
    }

    switch (request.method) {
        case AjaxMethod.Get:
        case AjaxMethod.Head:
            if (undefined !== request.payload && null !== request.payload) {
                throw `Payload unacceptable for ${request.method} method.`;
            }
            break;
    }

    if (!request.url) {
        throw "URL not specified.";
    }

    let xhr = new XMLHttpRequest();
    xhr.open(request.method, request.url);

    if (undefined !== request.payload && null !== request.payload) {
        xhr.setRequestHeader("Content-Type", request.contentType ? request.contentType : "application/x-www-form-urlencoded");
    }

    xhr.onload = (): boolean => {

        try {
            return request.done("object" === typeof xhr.response ? xhr.response : JSON.parse(xhr.responseText), xhr.status, xhr);
        } catch (error: any) {
            if (request.fail) {
                request.fail(error, xhr.status, xhr);
            } else {
                ErrorMessage({message: error, icon: "error"});
            }
        }

        return false;

    };

    xhr.onerror = (): string => {

        if (request.fail) {
            return request.fail(xhr.responseText, xhr.status, xhr);
        }

        return HandleAjaxError({text: xhr.responseText, status: xhr.status, xhr: xhr});

    };

    xhr.send(undefined !== request.payload && null !== request.payload ? JSON.stringify(request.payload) : null);

    return xhr;
}

/**
 * Interface to validate an AJAX response.
 */
export interface ValidateAjaxResponseInterface
{
    readonly data:          {success?: boolean, error?: string, redirect?: string}|string;
    readonly status:        number;
    readonly xhr:           XMLHttpRequest;
    readonly noSuccessKey?: boolean;
    readonly noAlert?:      boolean;
    readonly errorPrefix?:  string;
}

/**
 * Validate an AJAX response.
 * @param response ValidateAjaxResponseInterface
 * @returns boolean Successfully handled
 */
export function ValidateAjaxResponse(response: ValidateAjaxResponseInterface): boolean
{
    if ("object" !== typeof response.data) {
        ErrorMessage({message: "Unexpected response from server.", icon: "warning", noAlert: response.noAlert, prefix: response.errorPrefix});
        return false;
    }

    if (response.data.error) {
        ErrorMessage({message: response.data.error, icon: "error", noAlert: response.noAlert, prefix: response.errorPrefix});
        return false;
    }

    if (!response.noSuccessKey && !response.data.success) {
        ErrorMessage({message: "No response from server.", icon: "warning", noAlert: response.noAlert, prefix: response.errorPrefix});
        return false;
    }

    if (response.data.redirect) {
        window.location.href = response.data.redirect;
    }

    return true;

};

/**
 * Interface to handle an AJAX error.
 */
export interface HandleAjaxError
{
    readonly text:          string;
    readonly status:        number;
    readonly xhr:           XMLHttpRequest;
    readonly noAlert?:      boolean;
    readonly errorPrefix?:  string;
}

/**
 * Handle an AJAX error.
 * @param response HandleAjaxError
 * @returns string Error message
 */
export function HandleAjaxError(response: HandleAjaxError): string
{
    let error = "Unknown error.";

    if ("object" === typeof response.xhr.response && response.xhr.response.hasOwnProperty("error") && "string" === typeof response.xhr.response.error && response.xhr.response.error) {
        error = response.xhr.response.error;
    } else if (response.text) {
        error = response.text;
    } else if (!isNaN(response.status)) {
        error = `Code ${response.status} error.`;
    }

    ErrorMessage({message: error, icon: "error", noAlert: response.noAlert, prefix: response.errorPrefix});

    return error;
}
