/**
 * Enumeration for button size.
 */
export enum ButtonSize
{
    ExtraSmall  = "xs",
    Small       = "sm",
    Medium      = "md",
    Large       = "lg",
    ExtraLarge  = "xl",
}

/**
 * Cast a string to a ButtonSize.
 * @param   string|null s
 * @returns ButtonSize
 */
export function StringToButtonSize(s: string|null): ButtonSize
{
    if (null === s) {
        return ButtonSize.Medium;
    }

    switch (s) {
        case "xs":
        case "sm":
        case "md":
        case "lg":
        case "xl":
            return <ButtonSize>s;
    }

    throw "String invalid.";
}

/**
 * Enumeration for button type.
 */
export enum ButtonType
{
    Default     = "",
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
 * Cast a string to a ButtonType.
 * @param   string|null s
 * @returns ButtonType
 */
export function StringToButtonType(s: string|null): ButtonType
{
    if (null === ButtonType) {
        return ButtonType.Primary;
    }

    switch (s) {
        case "default":
        case "primary":
        case "secondary":
        case "tertiary":
        case "inverse":
        case "info":
        case "success":
        case "warning":
        case "danger":
            return <ButtonType>s;

        case "error":
            return ButtonType.Danger; // Convert "error" to Bootstrap
    }

    throw "String invalid.";
}
