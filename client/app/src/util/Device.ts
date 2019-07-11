

interface Device {
    uuid: string
    isVirtual: boolean
}

const windowDevice : Device = (window as any).device || {
    uuid: "nought",
    isVirtual: true
};

const device = windowDevice;

export default device;