const MAX_ENTRIES = 240;
export const MAX_ROWS = 6;
const MAX_COLUMNS = 80;

const WINNER_SYMBOL = {
    banker: 'B',
    player: 'P',
    tie: 'T'
};

export function createRoadmapState() {
    return {
        bead: [],
        bigColumns: [],
        big: [],
        bigEye: [],
        small: [],
        roach: [],
        summary: {
            banker: 0,
            player: 0,
            tie: 0
        },
        lastSymbol: null
    };
}

function pushWithLimit(arr, value) {
    if (value === null || value === undefined) return;
    arr.push(value);
    if (arr.length > MAX_ENTRIES) {
        arr.splice(0, arr.length - MAX_ENTRIES);
    }
}

function columnHasRow(column, row) {
    return column.some(node => node.row === row);
}

function pushNode(column, node) {
    column.push(node);
    column.sort((a, b) => a.row - b.row);
}

function addTieToLast(columns) {
    if (!columns.length) return;
    const lastColumn = columns[columns.length - 1];
    if (!lastColumn.length) return;
    lastColumn[lastColumn.length - 1].ties = (lastColumn[lastColumn.length - 1].ties || 0) + 1;
}

function flattenBigColumns(columns) {
    const flat = [];
    columns.forEach((col, columnIdx) => {
        col.forEach(node => {
            flat.push({ ...node, column: columnIdx });
        });
    });
    return flat.slice(-MAX_ENTRIES);
}

function addBigRoadEntry(state, symbol) {
    const columns = state.bigColumns;

    if (!state.lastSymbol) {
        columns.push([{ symbol, row: 0, ties: 0 }]);
        state.lastSymbol = symbol;
        return { column: 0, row: 0 };
    }

    if (columns.length > MAX_COLUMNS) {
        columns.splice(0, columns.length - MAX_COLUMNS);
    }

    if (symbol !== state.lastSymbol) {
        const newColumn = [{ symbol, row: 0, ties: 0 }];
        columns.push(newColumn);
        state.lastSymbol = symbol;
        return { column: columns.length - 1, row: 0 };
    }

    const currentColumn = columns[columns.length - 1];
    const lastNode = currentColumn[currentColumn.length - 1];
    const nextRow = lastNode.row + 1;

    if (nextRow < MAX_ROWS && !columnHasRow(currentColumn, nextRow)) {
        pushNode(currentColumn, { symbol, row: nextRow, ties: 0 });
        return { column: columns.length - 1, row: nextRow };
    }

    const newColumn = [];
    const row = Math.min(lastNode.row, MAX_ROWS - 1);
    pushNode(newColumn, { symbol, row, ties: 0 });
    columns.push(newColumn);
    return { column: columns.length - 1, row };
}

function computeDerivedColor(columns, columnIndex, rowIndex, gap) {
    if (columnIndex < gap) return null;
    const leftColumn = columns[columnIndex - 1];
    const compareColumn = columns[columnIndex - gap];
    if (!leftColumn || !compareColumn) return null;

    if (rowIndex === 0) {
        const leftLength = leftColumn.length;
        const compareLength = compareColumn.length;
        return leftLength === compareLength ? 'R' : 'B';
    }

    const hasCell = compareColumn.some(node => node.row === rowIndex);
    return hasCell ? 'R' : 'B';
}

export function updateRoadmaps(state, result) {
    const symbol = WINNER_SYMBOL[result.winner];

    if (result.winner === 'tie') {
        state.summary.tie += 1;
        pushWithLimit(state.bead, 'T');
        addTieToLast(state.bigColumns);
        state.big = flattenBigColumns(state.bigColumns);
        return state;
    }

    state.summary[result.winner] += 1;
    pushWithLimit(state.bead, symbol);

    const nodePosition = addBigRoadEntry(state, symbol);
    state.big = flattenBigColumns(state.bigColumns);

    const bigEyeColor = computeDerivedColor(state.bigColumns, nodePosition.column, nodePosition.row, 2);
    pushWithLimit(state.bigEye, bigEyeColor);

    const smallColor = computeDerivedColor(state.bigColumns, nodePosition.column, nodePosition.row, 3);
    pushWithLimit(state.small, smallColor);

    const roachColor = computeDerivedColor(state.bigColumns, nodePosition.column, nodePosition.row, 4);
    pushWithLimit(state.roach, roachColor);

    return state;
}
