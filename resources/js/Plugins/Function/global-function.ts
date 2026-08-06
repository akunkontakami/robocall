// export const routeAppendParam = (params: any, silent = true) => {
//     const newUrl = new URL(window.location.href);
//     for (var key in params) {
//         newUrl.searchParams.set(key, params[key]);
//     }
//     window.history.pushState({}, '', newUrl);
//     if (!silent) {
//         window.dispatchEvent(new Event('changeUrlParameter'));
//     }
// }
import { router } from '@inertiajs/vue3';
export const routeAppendParam = (params: Record<string, any>, silent = true, callback?: any) => {
     const url = new URL(window.location.href);

     for (const key in params) {
          url.searchParams.set(key, params[key]);
     }

     router.get(url.toString(), {}, {
          preserveState: true,
          preserveScroll: true,
          replace: true,
          onFinish: () => {
               if (!silent) {
                    window.dispatchEvent(new Event('changeUrlParameter'));
               }
               if (callback) {
                    const f = callback as any
                    f()
               }
          }
     });

};

export const removeAllUrlParameter = (silent = true) => {
    const url = new URL(window.location.href);
    window.history.replaceState({}, document.title, `${url.origin}${url.pathname}`);
    if (!silent) {
        window.dispatchEvent(new Event('changeUrlParameter'));
    }
}
export const removeURLParameter = (params: any, silent = true) => {
    const url = new URL(window.location.href);
    for (var key in params) {
        url.searchParams.delete(params[key]);
    }
    window.history.replaceState({}, document.title, url.toString());
    if (!silent) {
        window.dispatchEvent(new Event('changeUrlParameter'));
    }
}

export const getAllQueryParameter = () => {
    const entries = new URL(window.location.href).searchParams.entries();
    const result: any = {}
    for (const [key, value] of entries) {
        result[key] = value;
    }
    return result;
}

export const getQueryParam = (key: string,defaultValue?:string) => {
    return new URLSearchParams(window.location.search).get(key) || defaultValue
}

export function getArrayParamsFromUrl(name: string): string[] {
    const urlParams = new URLSearchParams(window.location.search);
    const arrayParams: string[] = [];

    for (const [key, value] of urlParams.entries()) {
        if (key.startsWith(name)) {
            arrayParams.push(value);
        }
    }

    return arrayParams;
}


export const isNumber = (evt: any) => {
    if (evt.target.value.length >= 1) {
        evt.preventDefault()
    }
    var charCode = evt.which ? evt.which : evt.keyCode;
    if (
        charCode > 31 &&
        (charCode < 48 || charCode > 57) &&
        [46, 47, 44, 43, 45, 101].includes(charCode)
    )
        evt.preventDefault();
    return true;
};
export const disableCopyPaste = (event: any) => {
    event.preventDefault();
};

export const startCountdownInterval = (token: string, key: any, setCountdown: any, setKey: any) => {
    setCountdown("1m59s");
    if (key) {
        clearInterval(key);
    }
    const intervalDuration = parseInt(
        window.localStorage.getItem(`otp-interval-second-${token}`) ||
        "120"
    );
    let canResendOtpAt = new Date();
    canResendOtpAt.setSeconds(canResendOtpAt.getSeconds() + intervalDuration);

    setKey(
        setInterval(() => {
            let currentTime = new Date();
            const timeLeft = canResendOtpAt.getTime() - currentTime.getTime();
            let seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
            let minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
            window.localStorage.setItem(
                `otp-interval-second-${token}`,
                (minutes * 60 + seconds).toString()
            );
            if (timeLeft > 0) {
                setCountdown(`${minutes}m${seconds}s`);
            } else {
                window.localStorage.removeItem(
                        `otp-interval-second-${token}`
                );
                setCountdown('')
                clearInterval(key);
            }
        }, 1000)
    )
};


export const showAlert = (message: string, type = 'warning') => {
    var color = 'bg-online'
    if (type === 'warning') color = 'bg-offline'

    const alert = document.getElementById('alert-flash-message')
    if (alert) {
        const messageContainer = alert.querySelector('.message');
        if (messageContainer) {
            messageContainer.innerHTML = message
            alert.classList.add(color)
            alert.classList.remove('hidden')
            alert.classList.add('flex')

            setTimeout(() => {
                messageContainer.innerHTML = ''
                alert.classList.remove('flex')
                alert.classList.add('hidden')
                alert.classList.remove(color)
            }, 3000)
        }

    }
}

export const hideAlert = () => {
    const alert = document.getElementById('alert-flash-message')
    if (alert) {
        alert.classList.remove('flex')
        alert.classList.add('hidden')
        const messageContainer = alert.querySelector('.message');
        if (messageContainer) {
            messageContainer.innerHTML = ''
            alert.classList.remove('bg-online')
            alert.classList.remove('bg-offline')
        }

    }
}

export const updateUserHeaderValue = (key: string, value: string) => {
    if (key == 'profile') {
        const element = document.getElementById('header-user-profile');
        if (element) {
            element.setAttribute('src', value)
        }
    }
    if (key == 'name') {
        const element = document.getElementById('header-user-name')
        if (element) {
            element.innerHTML = value
        }
    }
}
export const compressImage = (file: any, maxWidth: any, maxHeight: any, outputFormat: any, quality: any, callback: any) => {
    const reader = new FileReader();
    reader.onload = function (event: any) {
        const img: any = new Image();

        img.onload = function () {
            // Calculate the new size to fit within the specified dimensions while maintaining the aspect ratio.
            let width = img.width;
            let height = img.height;
            if (width > maxWidth) {
                height *= maxWidth / width;
                width = maxWidth;
            }
            if (height > maxHeight) {
                width *= maxHeight / height;
                height = maxHeight;
            }

            // Create a canvas with the new size
            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;

            // Draw the image on the canvas
            const ctx: any = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0, width, height);

            // Convert the canvas to a data URL with the specified output format and quality
            const compressedImageData = canvas.toDataURL(`image/${outputFormat}`, quality);

            // Call the callback function with the compressed image data URL
            callback(compressedImageData);
        };

        img.src = event.target.result;
    };
    reader.readAsDataURL(file);
}

export const filterUnique = (value:any, index:any, array:any) => {
    return array.indexOf(value) === index;
}

export const strSlug = (str: any, separator = '-') => {
    return str
        .toLowerCase()
        .replace(/[^\w\s]/gi, '')
        .trim()
        .replace(/\s+/g, separator);
}

export const dateYmd = (date = new Date()) => {
    date = new Date(date)
    const day = date.getDate().toString().padStart(2, '0')
    const month = (date.getMonth() + 1).toString().padStart(2, '0')
    return `${date.getFullYear()}-${month}-${day}`
}

export const validateMinimumDateRange = (start: string, end: string, validate = 30) => {
    if (start && end) {
        var startDate = new Date(start);
        var endDate = new Date(end);
        if (endDate < startDate) {
            showAlert('The start date must be greater than the end date')
            return false;
        }

        var Difference_In_Time = endDate.getTime() - startDate.getTime();
        var Difference_In_Days = Difference_In_Time / (1000 * 3600 * 24);
        if (Difference_In_Days > validate) {
            showAlert(`Maximum range date is ${validate}`)
            return false;
        }
    }
    return true;
}
export const closeFilter = () => {
    document.getElementById('close-filter-hidden')?.click()
}

export const randomString = (length = 10) => {
    let result = '';
    const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    const charactersLength = characters.length;
    let counter = 0;
    while (counter < length) {
        result += characters.charAt(Math.floor(Math.random() * charactersLength));
        counter += 1;
    }
    return result;
}

export const clickId = (id: string) => {
document.getElementById(id)?.click()
}


export const validateGreaterDateRange = (start: string, end: string) => {
    if (start && end) {
        var startDate = new Date(start);
        var endDate = new Date(end);
        if (endDate < startDate) {
            showAlert('The start date must be greater than the end date')
            return false;
        }
    }
    return true;
}

export const numberFormat = (val: any): string => {
    if (val == null) return "";
    let str = typeof val === "number" ? val.toString() : String(val);

    return str
        .replace(/\D/g, "")
        .replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}
