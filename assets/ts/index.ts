/**
 * Generate a unique identity for a new entity.
 * @returns string Entity ID
 */
export function GenerateEntityID(): string
{
    let id = "xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx";
    return id.replace(/[xy]/g, function(c){
        let r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
}
