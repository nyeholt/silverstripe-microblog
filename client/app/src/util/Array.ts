// see https://stackoverflow.com/questions/12802383/extending-array-in-typescript
export {}

declare global {
    interface Array<T> {
        diff(a: Array<T>, comparator?: (a: T, b: T) => boolean): Array<T>
    }
}

Array.prototype.diff = function<T>(a: Array<T>, comparator?: (a: T, b: T) => boolean): Array<T> {
    return this.filter(function (i: T) { 
        if (comparator) {
            for (let elem of a) {
                if (comparator(i, elem)) {
                    return false;
                }
            }
            return true;
        } 

        return a.indexOf(i) < 0; 
    });
};