import styles from "./status.uniq.css";

const statusMessages = {
    200: {
        head: "Success",
        message: "Operation was successful",
        type: "success",
    },
    403: {
        head: "Forbidden",
        message: "You don't have enough permissions",
        type: "error",
    },
    404: {
        head: "Not found",
        message: "This item doesn't exist",
        type: "error"
    },
    422: {
        head: "Unprocessable entity",
        message: "There are some validation errors",
        type: "error"
    },
    500: {
        head: "Internal server error",
        message: "Something went wrong serverside.",
        type: "error"
    },
    600: {
        head: "Validation issues",
        message: "There are validation issues",
        type: "error"
    },
    ERR_NETWORK: {
        head: "Network Error",
        message: "Seems like there's a connection problem",
        type: "error",
    },
    wrong: {
        head: "Something Went Wrong",
        message: "Please reload and try again",
        type: "error",
    }
}

export const getMessage = (code, messages = {}) => {
    const message = {
        head: "",
        message: "",
        ...(statusMessages[code] || {}),
        ...(messages[code] || {}),
    };

    return message;
}

export default function Status({ code, show = ["head", "message"], messages = {}, css = true, center = true }) {
    const message = getMessage(code, messages);

    const showHead = show.indexOf("head") !== -1;
    const showMessage = show.indexOf("message") !== -1;
    const classNames = ['d5_status_wrapper', `d5_status_${code}`];

    if ( css ) {
        const codeClass = `code_${code}`;

        classNames.push(styles.status);
        classNames.push("d5dim");

        if ( styles[codeClass] ) {
            classNames.push(styles[codeClass]);
        }

        if ( center ) {
            classNames.push(styles.center);
        }
    }

    return (
        <>
            {(showHead || showMessage) && (
                <div className={classNames.join(' ')}>
                    {showHead && message?.head && (<div className={`d5_status_head ${styles.head}`}>{message.head}</div>)}
                    {showMessage && message?.message && (<div className={`d5_status_message ${styles.message}`}>{message.message}</div>)}
                </div>
            )}
        </>
    )
}