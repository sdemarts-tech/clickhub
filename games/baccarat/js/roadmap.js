const MAX_ENTRIES = 240;
const MAX_ROWS = 6;

const WINNER_SYMBOL = {
    banker: 'B',
    player: 'P',
    tie: 'T'
};

export function createRoadmapState() {
    return {
        bead: [],
        big: [],
        bigColumns: [],
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

function flattenBigColumns(columns) {
    const flat = [];
    columns.forEach((col, colIdx) => {
        col.forEach((node, rowIdx) => {
            flat.push({ ...node, column: colIdx, row: rowIdx });
        });
    });
    if (flat.length > MAX_ENTRIES) {
        return flat.slice(flat.length - MAX_ENTRIES);
    }
    return flat;
}

function addBigRoadEntry(state, symbol) {
    const columns = state.bigColumns;

    if (!state.lastSymbol) {
        columns.push([{ symbol, ties: 0 }]);
        state.lastSymbol = symbol;
        return { column: 0, row: 0 };
    }
    
    // prune old columns if necessary
    if (columns.length > 40) {
        columns.shift();
    }

    if (symbol === state.lastSymbol) {
        const currentColumn = columns[columns.length - 1];
        let rowIdx = currentColumn.length;
        const prevColumn = columns.length > 1 ? columns[columns.length - 2] : null;

        let startNewColumn = false;
        if (rowIdx >= MAX_ROWS - 1) {
            startNewColumn = true;
        } else if (prevColumn && prevColumn.length <= rowIdx) {
            startNewColumn = true;
        }

        if (!startNewColumn) {
            currentColumn.push({ symbol, ties: 0 });
            return { column: columns.length - 1, row: rowIdx };
        }

        columns.push([{ symbol, ties: 0 }]);
        return { column: columns.length - 1, row: 0 };
    }

    columns.push([{ symbol, ties: 0 }]);
    state.lastSymbol = symbol;
    return { column: columns.length - 1, row: 0 };
}

function addTieToLast(columns) {
    if (!columns.length) return;
    const lastColumn = columns[columns.length - 1];
    if (!lastColumn.length) return;
    lastColumn[lastColumn.length - 1].ties = (lastColumn[lastColumn.length - 1].ties || 0) + 1;
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

    const hasCell = compareColumn.length > rowIndex ? compareColumn[rowIndex] : null;
    return hasCell ? 'R' : 'B';
}

function pushWithLimit(arr, value) {
    if (!value) return;
    arr.push(value);
    if (arr.length > MAX_ENTRIES) arr.shift();
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
