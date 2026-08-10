const translatedPasskeyErrors: Readonly<Record<string, string>> = {
    'Passkeys are not supported in this browser.':
        'Tento prohlížeč nepodporuje přístupové klíče.',
    'The passkey operation was cancelled.':
        'Operace s přístupovým klíčem byla zrušena.',
    'This device is already registered as a passkey.':
        'Toto zařízení už je zaregistrované jako přístupový klíč.',
    'An unknown error occurred.': 'Došlo k neznámé chybě.',
    'Unable to sign in with this account.':
        'Pomocí tohoto účtu se nepodařilo přihlásit.',
    'Unable to register passkey. Please try again.':
        'Přístupový klíč se nepodařilo zaregistrovat. Zkuste to znovu.',
    'Unable to register this passkey.':
        'Tento přístupový klíč se nepodařilo zaregistrovat.',
    'Unable to verify passkey. Please try again.':
        'Přístupový klíč se nepodařilo ověřit. Zkuste to znovu.',
    'Passkey not recognized. It may have been removed from your account.':
        'Přístupový klíč nebyl rozpoznán. Možná byl z vašeho účtu odebrán.',
    'Unauthenticated.': 'Nejste přihlášeni.',
    'CSRF token mismatch.':
        'Platnost relace vypršela. Obnovte stránku a zkuste to znovu.',
};

const czechCharacters = /[áčďéěíňóřšťúůýž]/i;

export function localizePasskeyError(
    message: string | null | undefined,
): string | undefined {
    if (!message) {
        return undefined;
    }

    if (translatedPasskeyErrors[message]) {
        return translatedPasskeyErrors[message];
    }

    if (message.startsWith("Passkeys can't be used on ")) {
        return 'Přístupové klíče nelze na této doméně použít. Pro místní vývoj použijte localhost.';
    }

    const failedStatus = message.match(/^Request failed with status (\d+)$/);

    if (failedStatus) {
        return `Požadavek se nezdařil (stav ${failedStatus[1]}).`;
    }

    if (czechCharacters.test(message)) {
        return message;
    }

    return 'Operaci s přístupovým klíčem se nepodařilo dokončit. Zkuste to znovu.';
}
