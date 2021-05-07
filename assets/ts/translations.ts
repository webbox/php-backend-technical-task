import Translator from "bazinga-translator";
import { StringToAjaxMethod, AjaxRequest, ValidateAjaxResponse, HandleAjaxError } from "./ajax";

const translationsLocalStorageKey   = "_translations";
const translationsLoadedEvent       = new Event("translationsLoaded");

export function LoadTranslations()
{
    let translationsUrl: string;
    if (!(translationsUrl = <string>document.documentElement.getAttribute("data-translations-url"))) {
        throw "Translations URL not specified.";
    }

    AjaxRequest({
        method:         StringToAjaxMethod("GET"),
        url:            translationsUrl,
        contentType:    "json",
        done:           (data: object|string, status: number, xhr: XMLHttpRequest): boolean => {

            if (!ValidateAjaxResponse({data: data, status: status, xhr: xhr, noSuccessKey: true})) {
                return false;
            }

            let translationJSON: string;
            switch (typeof data) {
                case "object":
                    translationJSON = JSON.stringify(data);
                    break;
                case "string":
                    translationJSON = data;
                    break;
                default:
                    return false;
            }

            if (!Translator.fromJSON(translationJSON)) {
                return false;
            }
            window.localStorage.setItem(translationsLocalStorageKey, translationJSON);

            console.debug("Translations loaded successfully from server.");
            document.documentElement.dispatchEvent(translationsLoadedEvent);
            return true;

        },
        fail:           (error: string, status: number, xhr: XMLHttpRequest): string => {
            let translationsLocalStorageData: string    = "";
            let translationsLoadedFromLocal: boolean    = false;

            if (
                window.localStorage.hasItem(translationsLocalStorageKey) &&
                (translationsLocalStorageData = <string>window.localStorage.getItem(translationsLocalStorageKey))
            ) {
                if (Translator.fromJSON(JSON.parse(translationsLocalStorageData))) {
                    console.debug("Translations loaded successfully from cache.");
                    document.documentElement.dispatchEvent(translationsLoadedEvent);
                    translationsLoadedFromLocal = true;
                } else {
                    window.localStorage.removeItem(translationsLocalStorageKey);
                }
            }

            return HandleAjaxError({text: xhr.responseText, status: xhr.status, xhr: xhr, noAlert: translationsLoadedFromLocal});
        },
    });
}

/**
 * Load translations immediately.
 */
 try {
    LoadTranslations();
} catch (e: any) {
    console.error(e);
}
