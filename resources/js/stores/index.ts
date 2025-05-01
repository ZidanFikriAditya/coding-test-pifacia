import { reactive } from "vue";

type TableStoreType = {
    search: string;
    page: number;
    sorting: {
        key: string;
        order: 'asc' | 'desc';
    };
    perPage: number;
    selected: any[];
    resetState: () => void;
    resetOrder: () => void;
}

export const tableStore = reactive<TableStoreType>({
    search: '',
    page: 1,
    sorting: {
        key: 'id',
        order: 'asc',
    },
    perPage: 10,
    selected: [],
    resetState() {
        this.search = '';
        this.page = 1;
        this.sorting.key = 'created_at';
        this.sorting.order = 'desc';
        this.perPage = 10;
        this.selected = [];
    },
    resetOrder() {
        this.sorting.key = 'id';
        this.sorting.order = 'asc';
    },
});