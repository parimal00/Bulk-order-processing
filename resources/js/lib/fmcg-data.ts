export type Kpi = {
    label: string;
    value: string;
    delta: string;
    trend: 'up' | 'down' | 'neutral';
};

export type UploadRecord = {
    id: string;
    customer: string;
    source: 'CSV' | 'XLSX' | 'API';
    rows: number;
    validPercent: number;
    status:
        | 'uploaded'
        | 'validating'
        | 'ready'
        | 'processing'
        | 'failed'
        | 'completed';
    createdAt: string;
    owner: string;
};

export type ProcessingJob = {
    id: string;
    uploadId: string;
    step: string;
    progress: number;
    status: 'queued' | 'running' | 'retrying' | 'failed' | 'completed';
    elapsed: string;
};

export type ApprovalRecord = {
    orderNo: string;
    customer: string;
    amount: string;
    margin: string;
    risk: 'low' | 'medium' | 'high';
    reasons: string[];
    submittedAt: string;
};

export type OrderRecord = {
    orderNo: string;
    customer: string;
    status:
        | 'new'
        | 'allocated'
        | 'partially_fulfilled'
        | 'backordered'
        | 'completed';
    fulfillment: number;
    total: string;
    updatedAt: string;
};

export type OrderLine = {
    sku: string;
    product: string;
    requestedQty: number;
    allocatedQty: number;
    backorderQty: number;
    unitPrice: string;
};

export type ReconciliationRecord = {
    orderNo: string;
    internalState: string;
    externalState: string;
    mismatch:
        | 'none'
        | 'qty_mismatch'
        | 'price_mismatch'
        | 'missing_external'
        | 'sync_timeout';
    lastSync: string;
};

export type AuditRecord = {
    timestamp: string;
    actor: string;
    action: string;
    entity: string;
    details: string;
};

export const dashboardKpis: Kpi[] = [
    { label: 'Today Uploads', value: '42', delta: '+18%', trend: 'up' },
    { label: 'Lines Processed', value: '18,240', delta: '+12%', trend: 'up' },
    { label: 'Approval Backlog', value: '16', delta: '-9%', trend: 'down' },
    { label: 'Sync Failures', value: '7', delta: '+2', trend: 'neutral' },
    { label: 'Avg Process Time', value: '4m 11s', delta: '-33s', trend: 'down' },
];

export const uploads: UploadRecord[] = [
    {
        id: 'UPL-2401',
        customer: 'Metro Retail Group',
        source: 'CSV',
        rows: 1280,
        validPercent: 97,
        status: 'ready',
        createdAt: 'May 12, 2026 09:12',
        owner: 'Anita Rai',
    },
    {
        id: 'UPL-2402',
        customer: 'EverFresh Supermart',
        source: 'XLSX',
        rows: 834,
        validPercent: 88,
        status: 'failed',
        createdAt: 'May 12, 2026 09:48',
        owner: 'Binod Karki',
    },
    {
        id: 'UPL-2403',
        customer: 'CityBazaar Stores',
        source: 'API',
        rows: 2200,
        validPercent: 100,
        status: 'processing',
        createdAt: 'May 12, 2026 10:06',
        owner: 'Sita Gurung',
    },
    {
        id: 'UPL-2404',
        customer: 'NexMart Wholesale',
        source: 'CSV',
        rows: 640,
        validPercent: 95,
        status: 'completed',
        createdAt: 'May 12, 2026 10:41',
        owner: 'Rahul Singh',
    },
];

export const processingJobs: ProcessingJob[] = [
    {
        id: 'JOB-9012',
        uploadId: 'UPL-2403',
        step: 'Pricing and MOQ Rules',
        progress: 63,
        status: 'running',
        elapsed: '03m 02s',
    },
    {
        id: 'JOB-9013',
        uploadId: 'UPL-2401',
        step: 'Inventory Allocation',
        progress: 100,
        status: 'completed',
        elapsed: '02m 41s',
    },
    {
        id: 'JOB-9014',
        uploadId: 'UPL-2402',
        step: 'Validation Retry',
        progress: 46,
        status: 'retrying',
        elapsed: '01m 19s',
    },
    {
        id: 'JOB-9015',
        uploadId: 'UPL-2399',
        step: 'ERP Sync Publish',
        progress: 100,
        status: 'failed',
        elapsed: '05m 27s',
    },
];

export const approvals: ApprovalRecord[] = [
    {
        orderNo: 'SO-55012',
        customer: 'EverFresh Supermart',
        amount: '$24,860',
        margin: '9.2%',
        risk: 'high',
        reasons: ['Low margin', 'Large discount override'],
        submittedAt: 'May 12, 2026 10:22',
    },
    {
        orderNo: 'SO-55013',
        customer: 'Metro Retail Group',
        amount: '$11,430',
        margin: '13.4%',
        risk: 'medium',
        reasons: ['Backorder > 20%'],
        submittedAt: 'May 12, 2026 10:46',
    },
    {
        orderNo: 'SO-55014',
        customer: 'NexMart Wholesale',
        amount: '$8,090',
        margin: '15.1%',
        risk: 'low',
        reasons: ['MOQ manual override'],
        submittedAt: 'May 12, 2026 11:03',
    },
];

export const orders: OrderRecord[] = [
    {
        orderNo: 'SO-55012',
        customer: 'EverFresh Supermart',
        status: 'backordered',
        fulfillment: 72,
        total: '$24,860',
        updatedAt: '11:12',
    },
    {
        orderNo: 'SO-55013',
        customer: 'Metro Retail Group',
        status: 'allocated',
        fulfillment: 100,
        total: '$11,430',
        updatedAt: '11:08',
    },
    {
        orderNo: 'SO-55014',
        customer: 'NexMart Wholesale',
        status: 'partially_fulfilled',
        fulfillment: 81,
        total: '$8,090',
        updatedAt: '10:59',
    },
    {
        orderNo: 'SO-55015',
        customer: 'CityBazaar Stores',
        status: 'completed',
        fulfillment: 100,
        total: '$16,740',
        updatedAt: '10:34',
    },
];

export const orderLines: OrderLine[] = [
    {
        sku: 'SKU-CHP-120',
        product: 'Crispy Chips 120g',
        requestedQty: 180,
        allocatedQty: 150,
        backorderQty: 30,
        unitPrice: '$1.18',
    },
    {
        sku: 'SKU-JCE-1L',
        product: 'Orange Juice 1L',
        requestedQty: 240,
        allocatedQty: 240,
        backorderQty: 0,
        unitPrice: '$1.42',
    },
    {
        sku: 'SKU-CLN-500',
        product: 'Surface Cleaner 500ml',
        requestedQty: 120,
        allocatedQty: 90,
        backorderQty: 30,
        unitPrice: '$2.04',
    },
];

export const reconciliationRows: ReconciliationRecord[] = [
    {
        orderNo: 'SO-55012',
        internalState: 'backordered',
        externalState: 'allocated',
        mismatch: 'qty_mismatch',
        lastSync: 'May 12, 2026 11:14',
    },
    {
        orderNo: 'SO-55013',
        internalState: 'allocated',
        externalState: 'allocated',
        mismatch: 'none',
        lastSync: 'May 12, 2026 11:10',
    },
    {
        orderNo: 'SO-55014',
        internalState: 'partially_fulfilled',
        externalState: 'missing',
        mismatch: 'missing_external',
        lastSync: 'May 12, 2026 10:59',
    },
    {
        orderNo: 'SO-55015',
        internalState: 'completed',
        externalState: 'pending',
        mismatch: 'sync_timeout',
        lastSync: 'May 12, 2026 10:39',
    },
];

export const auditTrail: AuditRecord[] = [
    {
        timestamp: '2026-05-12 11:15:09',
        actor: 'Anita Rai',
        action: 'Order Approved',
        entity: 'SO-55013',
        details: 'Approved after auto-margin verification',
    },
    {
        timestamp: '2026-05-12 11:09:47',
        actor: 'System Job',
        action: 'Allocation Split',
        entity: 'SO-55012',
        details: 'Split into ready-ship and backorder lines',
    },
    {
        timestamp: '2026-05-12 11:03:24',
        actor: 'Binod Karki',
        action: 'Upload Re-validated',
        entity: 'UPL-2402',
        details: 'Corrected 33 invalid SKU rows',
    },
    {
        timestamp: '2026-05-12 10:57:31',
        actor: 'System Job',
        action: 'Sync Failed',
        entity: 'SO-55015',
        details: 'ERP timeout after 3 retries',
    },
];

export const chartThroughput = [
    { hour: '06:00', lines: 1100 },
    { hour: '07:00', lines: 1380 },
    { hour: '08:00', lines: 1620 },
    { hour: '09:00', lines: 2210 },
    { hour: '10:00', lines: 3150 },
    { hour: '11:00', lines: 2690 },
];

export const chartFailures = [
    { day: 'Mon', count: 9 },
    { day: 'Tue', count: 7 },
    { day: 'Wed', count: 6 },
    { day: 'Thu', count: 8 },
    { day: 'Fri', count: 4 },
    { day: 'Sat', count: 3 },
    { day: 'Sun', count: 5 },
];

export const validationErrors = [
    {
        row: 14,
        sku: 'SKU-ZZZ-109',
        issue: 'SKU not found',
        suggestion: 'Replace with active catalog SKU',
    },
    {
        row: 57,
        sku: 'SKU-CHP-120',
        issue: 'Quantity below MOQ (min 50)',
        suggestion: 'Adjust quantity to 50 or above',
    },
    {
        row: 91,
        sku: 'SKU-JCE-1L',
        issue: 'Duplicate line item',
        suggestion: 'Merge duplicate SKU quantities',
    },
];

export const pricingRules = [
    {
        customerTier: 'Tier A',
        category: 'Beverages',
        minQty: 120,
        discount: '8%',
        moq: 60,
    },
    {
        customerTier: 'Tier B',
        category: 'Snacks',
        minQty: 80,
        discount: '5%',
        moq: 40,
    },
    {
        customerTier: 'Tier C',
        category: 'Cleaning',
        minQty: 50,
        discount: '3%',
        moq: 30,
    },
];

export const inventoryPolicies = [
    {
        policy: 'Allocation Strategy',
        value: 'FIFO by warehouse priority',
        updatedAt: 'May 10, 2026',
    },
    {
        policy: 'Backorder Threshold',
        value: 'Trigger approval if > 20%',
        updatedAt: 'May 09, 2026',
    },
    {
        policy: 'Safety Stock',
        value: '15 days rolling demand',
        updatedAt: 'May 05, 2026',
    },
];

export const userRoles = [
    {
        name: 'Anita Rai',
        role: 'Ops',
        modules: 'Uploads, Validation, Reconciliation',
        activity: '22 actions today',
    },
    {
        name: 'Rahul Singh',
        role: 'Approver',
        modules: 'Approvals, Orders',
        activity: '9 approvals today',
    },
    {
        name: 'Sita Gurung',
        role: 'Admin',
        modules: 'Settings, Integrations, Users',
        activity: '4 policy edits today',
    },
];
