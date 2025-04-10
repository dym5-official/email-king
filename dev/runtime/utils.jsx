function formdata(formElement, defaultValues = {}) {
    const formData = new FormData(typeof formElement === "string" ? document.querySelector(formElement) : formElement);
    const formDataObject = {...defaultValues};

    for (const [key, value] of formData.entries()) {
        formDataObject[key] = value;
    }

    return formDataObject;
}

function arrMove(arr, from, to) {
    arr.splice(to, 0, arr.splice(from, 1)[0]);
}

const utils = {
    formdata,
    arrMove,
};

export default utils;