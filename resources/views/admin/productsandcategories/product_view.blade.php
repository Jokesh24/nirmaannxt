<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $product->name }} | Catalog View</title>
    @include('admin.includes.header_links')
    <link
        href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=Instrument+Sans:ital,wght@0,400;0,500;0,600;1,400&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --ff-head: 'Syne', sans-serif;
            --ff-body: 'Instrument Sans', sans-serif;
            --c0: #0a0a0a;
            --c1: #1c1c1c;
            --c2: #3d3d3d;
            --c3: #717171;
            --c4: #a0a0a0;
            --c5: #d4d4d4;
            --c6: #ebebeb;
            --c7: #f5f5f5;
            --c8: #fafafa;
            --white: #ffffff;
            --green: #16a34a;
            --green-s: #dcfce7;
            --red: #dc2626;
            --red-s: #fee2e2;
            --amber: #d97706;
            --amber-s: #fef3c7;
            --purple: #6d28d9;
            --purple-s: #ede9fe;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }

        body {
            font-family: var(--ff-body);
            background: var(--c7);
            color: var(--c0);
            font-size: 14px;
            line-height: 1.6;
        }

        /* TOP BAR */
        .pv-topbar {
            background: var(--white);
            border-bottom: 1px solid var(--c6);
            padding: 0 32px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .pv-crumb { display: flex; align-items: center; font-size: 13px; }
        .pv-crumb a {
            color: var(--c3);
            text-decoration: none;
            padding: 0 12px;
            height: 56px;
            display: flex;
            align-items: center;
            border-right: 1px solid var(--c6);
            transition: color 0.15s, background 0.15s;
        }
        .pv-crumb a:first-child { padding-left: 0; }
        .pv-crumb a:hover { color: var(--c0); background: var(--c7); }
        .pv-crumb-cur {
            color: var(--c0);
            font-weight: 600;
            padding: 0 12px;
            max-width: 260px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .pv-topbar-right { display: flex; align-items: center; gap: 10px; }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            height: 36px;
            padding: 0 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            font-family: var(--ff-body);
            cursor: pointer;
            text-decoration: none;
            border: 1px solid transparent;
            transition: all 0.15s;
            white-space: nowrap;
        }
        .btn-ghost { background: transparent; border-color: var(--c5); color: var(--c2); }
        .btn-ghost:hover { background: var(--c7); color: var(--c0); border-color: var(--c4); }
        .btn-dark { background: var(--c0); color: var(--white); border-color: var(--c0); }
        .btn-dark:hover { background: var(--c1); color: var(--white); }

        /* SPLIT LAYOUT */
        .pv-main {
            display: grid;
            grid-template-columns: 460px 1fr;
            min-height: calc(100vh - 56px);
        }

        /* LEFT PANEL */
        .pv-left {
            background: var(--white);
            border-right: 1px solid var(--c6);
            display: flex;
            flex-direction: column;
            position: sticky;
            top: 56px;
            height: calc(100vh - 56px);
            overflow: hidden;
        }
        .pv-img-stage {
            flex: 1;
            position: relative;
            overflow: hidden;
            background: var(--c7);
            min-height: 0;
        }
        .pv-img-stage img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: opacity 0.2s ease;
        }
        .pv-img-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 20px 24px;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.55) 0%, transparent 100%);
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 10px;
        }
        .pv-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 13px;
            border-radius: 999px;
            font-size: 11.5px;
            font-weight: 600;
        }
        .pill-active { background: #16a34a; color: #fff; }
        .pill-inactive { background: rgba(255, 255, 255, 0.18); color: #fff; border: 1px solid rgba(255, 255, 255, 0.3); }

        .pv-img-flags { display: flex; gap: 6px; flex-wrap: wrap; justify-content: flex-end; }
        .img-flag {
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: 0.4px;
            text-transform: uppercase;
        }
        .if-featured { background: rgba(109, 40, 217, 0.82); color: #fff; }
        .if-trending { background: rgba(180, 83, 9, 0.82); color: #fff; }

        /* THUMBNAIL STRIP */
        .pv-thumb-strip { border-top: 1px solid var(--c6); padding: 14px 16px 16px; flex-shrink: 0; }
        .pv-thumb-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--c4);
            margin-bottom: 10px;
        }
        .pv-thumb-row {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding-bottom: 4px;
            scrollbar-width: thin;
            scrollbar-color: var(--c5) transparent;
        }
        .pv-thumb-item { flex-shrink: 0; text-align: center; }
        .pv-thumb {
            width: 58px;
            height: 58px;
            object-fit: cover;
            border-radius: 7px;
            border: 1.5px solid var(--c6);
            cursor: pointer;
            display: block;
            transition: border-color 0.15s, transform 0.15s;
        }
        .pv-thumb:hover { border-color: var(--c3); transform: translateY(-1px); }
        .pv-thumb.active { border-color: var(--c0); border-width: 2px; }
        .pv-thumb-tag {
            font-size: 9px;
            color: var(--c4);
            margin-top: 4px;
            font-weight: 600;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }

        /* RIGHT PANEL */
        .pv-right { overflow-y: auto; padding: 32px 36px 64px; background: var(--c8); }
        .pv-prod-head {
            margin-bottom: 24px;
            padding-bottom: 24px;
            border-bottom: 1px solid var(--c6);
        }
        .pv-prod-name {
            font-family: var(--ff-head);
            font-size: 30px;
            font-weight: 700;
            color: var(--c0);
            line-height: 1.15;
            letter-spacing: -0.5px;
            margin-bottom: 10px;
        }
        .pv-prod-meta {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            color: var(--c3);
            font-size: 13px;
        }
        .pv-prod-sku {
            font-family: 'SF Mono', 'Fira Code', monospace;
            font-size: 12px;
            background: var(--c7);
            border: 1px solid var(--c6);
            padding: 2px 9px;
            border-radius: 4px;
            color: var(--c2);
        }
        .pv-sep { color: var(--c5); }

        /* SECTION */
        .pv-section {
            background: var(--white);
            border: 1px solid var(--c6);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 14px;
        }
        .pv-section-head {
            padding: 12px 20px;
            border-bottom: 1px solid var(--c6);
            background: var(--c8);
        }
        .pv-section-title {
            font-family: var(--ff-head);
            font-size: 11.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: var(--c2);
            background: var(--c6);
            padding: 6px 14px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            border: 1px solid var(--c5);
        }

        /* PRICE GRID - DUAL TIER */
        .pv-price-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1px;
            background: var(--c6);
        }
        .pv-price-cell {
            background: var(--white);
            padding: 18px 20px;
        }
        .pv-price-lbl {
            font-size: 10.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: var(--c4);
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .pv-price-num {
            font-family: var(--ff-head);
            font-size: 26px;
            font-weight: 700;
            color: var(--c0);
            line-height: 1;
        }

        /* INFO GRID */
        .pv-info-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1px;
            background: var(--c6);
        }
        .pv-info-cell {
            background: var(--white);
            padding: 16px 20px;
        }
        .pv-info-lbl {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: var(--c4);
            margin-bottom: 5px;
        }
        .pv-info-val {
            font-size: 15px;
            font-weight: 700;
            color: var(--c0);
        }

        /* DESCRIPTION */
        .pv-section-body { padding: 20px; }
        .pv-desc-short {
            font-size: 14px;
            color: var(--c2);
            line-height: 1.75;
            padding: 14px 16px;
            background: var(--c8);
            border-radius: 0;
            border-left: 3px solid var(--c5);
            margin-bottom: 16px;
        }
        .pv-desc-full { font-size: 14px; color: var(--c2); line-height: 1.8; }
        .pv-desc-full .hidden-node { display: none !important; }
        .pv-desc-full .reveal-node { display: block; animation: textFadeIn 0.3s ease forwards; }

        @keyframes textFadeIn {
            from { opacity: 0; transform: translateY(-4px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .rm-toggle {
            color: var(--c0);
            background: var(--c6);
            border: 1px solid var(--c5);
            font-family: var(--ff-body);
            font-weight: 600;
            font-size: 12.5px;
            cursor: pointer;
            padding: 6px 14px;
            border-radius: 6px;
            margin-top: 12px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s ease;
        }
        .rm-toggle:hover { background: var(--c5); border-color: var(--c4); }

        /* VARIANT ACCORDION */
        .pv-var-header {
            display: flex;
            align-items: baseline;
            gap: 10px;
            margin: 28px 0 14px;
        }
        .pv-var-title { font-family: var(--ff-head); font-size: 18px; font-weight: 700; color: var(--c0); }
        .pv-var-count {
            font-size: 12px;
            font-weight: 600;
            background: var(--c6);
            color: var(--c3);
            padding: 2px 10px;
            border-radius: 999px;
        }
        .pv-variant {
            background: var(--white);
            border: 1px solid var(--c6);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 16px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .pv-variant:hover { border-color: var(--c4); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03); }

        .pv-var-head-compact {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 18px;
            cursor: pointer;
            background: var(--white);
            user-select: none;
            transition: background 0.15s ease;
        }
        .pv-var-head-compact:hover { background: var(--c8); }
        .pv-var-head-left { display: flex; align-items: center; gap: 16px; }
        .pv-var-head-img { width: 46px; height: 46px; border-radius: 8px; object-fit: cover; border: 1px solid var(--c6); }
        .pv-var-head-ph {
            width: 46px;
            height: 46px;
            border-radius: 8px;
            border: 1px dashed var(--c5);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--c5);
            font-size: 20px;
        }
        .pv-var-head-title { font-family: var(--ff-head); font-size: 15px; font-weight: 700; color: var(--c0); line-height: 1.2; }
        .pv-var-head-sku { font-size: 12.5px; color: var(--c4); font-weight: 500; margin-top: 4px; }
        .pv-var-head-right { display: flex; align-items: center; gap: 16px; }
        .pv-var-status { font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 999px; }
        .vs-on { background: var(--green-s); color: var(--green); }
        .vs-off { background: var(--c6); color: var(--c3); }
        .pv-var-chevron { font-size: 16px; color: var(--c3); transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); }

        .pv-variant.is-open { border-color: var(--c3); }
        .pv-variant.is-open .pv-var-head-compact { background: var(--c8); border-bottom: 1px solid var(--c6); }
        .pv-variant.is-open .pv-var-chevron { transform: rotate(-180deg); }

        .pv-var-body { display: none; animation: slideDown 0.3s cubic-bezier(0.4, 0, 0.2, 1) forwards; }
        .pv-variant.is-open .pv-var-body { display: block; }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .pv-var-top { display: grid; grid-template-columns: 80px 1fr; }
        .pv-var-img-col {
            background: var(--c7);
            border-right: 1px solid var(--c6);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 96px;
            cursor: pointer;
        }
        .pv-var-img-col img { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; border: 1px solid var(--c6); transition: transform 0.15s; }
        .pv-var-img-col:hover img { transform: scale(1.06); }
        .pv-var-info { padding: 16px 18px; }

        .pv-var-row1 { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 12px; }
        .pv-var-sku { font-family: var(--ff-head); font-size: 15px; font-weight: 700; color: var(--c0); }
        .pv-var-name-hint { display: block; font-size: 13px; color: var(--c3); font-weight: 500; margin-top: 2px; }

        .pv-var-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
        .pvs-lbl { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--c4); margin-bottom: 3px; }
        .pvs-val { font-size: 14px; font-weight: 600; color: var(--c0); }

        .pv-var-meta-wrapper {
            background: #f8fafc;
            border-top: 1px solid var(--c6);
            padding: 12px 18px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .pv-meta-line { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .pv-meta-title-label { font-size: 10px; font-weight: 800; text-transform: uppercase; color: var(--c3); letter-spacing: 0.5px; min-width: 85px; }
        .pv-meta-pill {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 2px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            color: #334155;
        }
        .pv-meta-pill strong { color: var(--c0); font-weight: 700; }

        .pv-var-foot {
            border-top: 1px solid var(--c6);
            padding: 11px 18px;
            background: var(--c8);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }
        .pv-var-note { font-size: 12.5px; color: var(--c3); display: flex; align-items: flex-start; gap: 6px; }
        .pv-var-flags { display: flex; gap: 6px; }
        .vf { font-size: 10px; font-weight: 700; padding: 3px 9px; border-radius: 999px; text-transform: uppercase; letter-spacing: 0.3px; }
        .vf-feat { background: var(--purple-s); color: var(--purple); }
        .vf-trend { background: var(--amber-s); color: var(--amber); }

        .pv-vgal { border-top: 1px solid var(--c6); padding: 10px 18px 12px; display: flex; align-items: center; gap: 10px; }
        .pv-vgal-lbl { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--c4); flex-shrink: 0; }
        .pv-vgal img { width: 36px; height: 36px; object-fit: cover; border-radius: 5px; border: 1px solid var(--c6); cursor: pointer; transition: transform 0.15s, border-color 0.15s; }
        .pv-vgal img:hover { transform: scale(1.1); border-color: var(--c0); }

        .pv-no-var { background: var(--white); border: 1px solid var(--c6); border-radius: 12px; padding: 40px; text-align: center; color: var(--c4); }
        .pv-audit { display: flex; align-items: center; gap: 8px; margin-top: 24px; font-size: 12px; color: var(--c4); border-top: 1px solid var(--c6); padding-top: 20px; }

        @media (max-width: 1100px) { .pv-main { grid-template-columns: 380px 1fr; } }
        @media (max-width: 860px) {
            .pv-main { grid-template-columns: 1fr; }
            .pv-left { position: relative; top: 0; height: auto; }
            .pv-img-stage { min-height: 340px; }
            .pv-right { padding: 24px 20px 48px; }
            .pv-price-grid, .pv-info-grid, .pv-var-stats { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 520px) {
            .pv-topbar { padding: 0 16px; }
            .pv-prod-name { font-size: 22px; }
            .pv-price-grid { grid-template-columns: 1fr; }
            .pv-var-top { grid-template-columns: 70px 1fr; }
        }
    </style>
</head>

<body>
    @include('admin.includes.sidebar')
    @include('admin.includes.header')

    <div class="content">

        <div class="pv-topbar">
            <div class="pv-crumb">
                <a href="{{ route('admin.dashboard') }}"><i class="bi bi-house"></i></a>
                <a href="{{ route('admin.products.manage') }}">Products</a>
                <span class="pv-crumb-cur">{{ $product->name }}</span>
            </div>
            <div class="pv-topbar-right">
                <a href="{{ route('admin.products.manage') }}" class="btn btn-ghost">
                    <i class="bi bi-arrow-left"></i><span>Back</span>
                </a>
                <a href="{{ route('admin.products.edit', $product->id) }}" class="btn btn-dark">
                    <i class="bi bi-pencil-square"></i><span>Edit Matrix</span>
                </a>
            </div>
        </div>

        <div class="pv-main">

            <div class="pv-left">
                <div class="pv-img-stage">
                    <img src="{{ asset('uploads/products/' . $product->image) }}" id="mainImage" alt="{{ $product->name }}">
                    <div class="pv-img-overlay">
                        <span class="pv-status-pill {{ $product->status == 1 ? 'pill-active' : 'pill-inactive' }}">
                            <i class="bi bi-{{ $product->status == 1 ? 'check2-circle' : 'dash-circle' }}"></i>
                            {{ $product->status == 1 ? 'Active' : 'Inactive' }}
                        </span>
                        <div class="pv-img-flags">
                            @if($product->is_featured)
                                <span class="img-flag if-featured"><i class="bi bi-star-fill me-1"></i>Featured</span>
                            @endif
                            @if($product->is_trending)
                                <span class="img-flag if-trending"><i class="bi bi-lightning-fill me-1"></i>Trending</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="pv-thumb-strip">
                    <div class="pv-thumb-label">Catalog Media Stream</div>
                    <div class="pv-thumb-row">
                        <div class="pv-thumb-item">
                            <img src="{{ asset('uploads/products/' . $product->image) }}" class="pv-thumb active"
                                data-src="{{ asset('uploads/products/' . $product->image) }}" alt="Main Display">
                            <div class="pv-thumb-tag">Main</div>
                        </div>
                        @if($product->gallery)
                            @foreach($product->gallery as $gal)
                                <div class="pv-thumb-item">
                                    <img src="{{ asset('uploads/products/gallery/' . $gal) }}" class="pv-thumb"
                                        data-src="{{ asset('uploads/products/gallery/' . $gal) }}" alt="Gallery Item">
                                    <div class="pv-thumb-tag">Gal</div>
                                </div>
                            @endforeach
                        @endif
                        @foreach($product->variants as $v)
                            @if($v->image && $v->image !== $product->image)
                                <div class="pv-thumb-item">
                                    <img src="{{ asset('uploads/products/' . $v->image) }}" class="pv-thumb"
                                        data-src="{{ asset('uploads/products/' . $v->image) }}"
                                        title="{{ $v->variant_name }}" alt="Variant Hero">
                                    <div class="pv-thumb-tag">Var</div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="pv-right">

                <div class="pv-prod-head">
                    <h1 class="pv-prod-name">{{ $product->name }}</h1>
                    <div class="pv-prod-meta">
                        <span class="pv-prod-sku">SKU Container ID: {{ $product->sku ?? 'N/A' }}</span>
                        <span class="pv-sep">·</span>
                        <span>{{ $product->category->name ?? 'Unassigned' }}</span>
                        <i class="bi bi-chevron-right" style="font-size:11px;color:var(--c5);"></i>
                        <span>{{ $product->subcategory->name ?? 'Unassigned' }}</span>
                    </div>
                </div>

                <div class="pv-section">
                    <div class="pv-section-head">
                        <div class="pv-section-title">Delivery & Pricing Tiers</div>
                    </div>
                    <div class="pv-price-grid">
                        <div class="pv-price-cell">
                            <div class="pv-price-lbl text-primary"><i class="bi bi-truck me-1"></i> Standard Delivery</div>
                            <div class="d-flex align-items-baseline gap-2">
                                <div class="pv-price-num">₹{{ number_format($product->offer_price ?? $product->price, 2) }}</div>
                                @if($product->offer_price && $product->offer_price < $product->price)
                                    <div class="text-decoration-line-through text-muted" style="font-size: 16px;">₹{{ number_format($product->price, 2) }}</div>
                                @endif
                            </div>
                        </div>
                        <div class="pv-price-cell" style="background:#f0fdf4;">
                            <div class="pv-price-lbl text-success"><i class="bi bi-lightning-charge me-1"></i> Instant Delivery</div>
                            <div class="d-flex align-items-baseline gap-2">
                                <div class="pv-price-num text-success">₹{{ number_format($product->instant_offer_price ?? $product->instant_price, 2) }}</div>
                                @if($product->instant_offer_price && $product->instant_offer_price < $product->instant_price)
                                    <div class="text-decoration-line-through text-muted" style="font-size: 16px;">₹{{ number_format($product->instant_price, 2) }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pv-section">
                    <div class="pv-section-head">
                        <div class="pv-section-title">Master Identity Parameters</div>
                    </div>
                    <div class="pv-info-grid">
                        <div class="pv-info-cell">
                            <div class="pv-info-lbl">Base Quantity / Unit</div>
                            <div class="pv-info-val">{{ $product->qty }} {{ $product->unit ?? 'Units' }}</div>
                        </div>
                        <div class="pv-info-cell">
                            <div class="pv-info-lbl">Royalty Reward</div>
                            <div class="pv-info-val text-warning"><i class="bi bi-coin me-1"></i>{{ $product->royalty_percentage ?? 0 }}%</div>
                        </div>
                        <div class="pv-info-cell">
                            <div class="pv-info-lbl">Inventory Depth</div>
                            <div class="pv-info-val {{ $product->stock > 0 ? 'ok' : 'bad' }}">
                                {{ $product->stock }}
                            </div>
                        </div>
                        <div class="pv-info-cell">
                            <div class="pv-info-lbl">SLA (Delivery Time)</div>
                            <div class="pv-info-val">{{ $product->sla_days ?? 'N/A' }} Days</div>
                        </div>
                    </div>
                </div>

                <div class="pv-section">
                    <div class="pv-section-head">
                        <div class="pv-section-title">Global Descriptions</div>
                    </div>
                    <div class="pv-section-body">
                        @if($product->short_description)
                            <div class="pv-desc-short">{{ $product->short_description }}</div>
                        @endif
                        <div class="pv-desc-full" id="richDescriptionBody">{!! $product->description !!}</div>
                        <button id="readMoreToggle" class="rm-toggle" style="display: none;">
                            Read More <i class="bi bi-chevron-down"></i>
                        </button>
                    </div>
                </div>

                <div class="pv-var-header">
                    <div class="pv-var-title">Catalog Skus & Variations Mapping</div>
                    <span class="pv-var-count">{{ $product->variants->count() }} Variant Items</span>
                </div>

                @forelse($product->variants as $variant)
                    <div class="pv-variant">

                        <div class="pv-var-head-compact" onclick="toggleVariant(this)">
                            <div class="pv-var-head-left">
                                @if($variant->image)
                                    <img src="{{ asset('uploads/products/' . $variant->image) }}"
                                        alt="{{ $variant->sku }}" class="pv-var-head-img">
                                @else
                                    <div class="pv-var-head-ph"><i class="bi bi-box-seam"></i></div>
                                @endif
                                <div>
                                    <div class="pv-var-head-title">{{ $variant->variant_name ?? 'Unnamed Option' }}</div>
                                    <div class="pv-var-head-sku">{{ $variant->sku ?? 'No SKU Assigned' }}</div>
                                </div>
                            </div>
                            <div class="pv-var-head-right">
                                <span class="pv-var-status {{ $variant->status == 1 ? 'vs-on' : 'vs-off' }}">
                                    {{ $variant->status == 1 ? 'Active' : 'Inactive' }}
                                </span>
                                <i class="bi bi-chevron-down pv-var-chevron"></i>
                            </div>
                        </div>

                        <div class="pv-var-body">
                            <div class="pv-var-top">
                                <div class="pv-var-img-col"
                                    onclick="swapMain('{{ asset('uploads/products/' . ($variant->image ?? $product->image)) }}')">
                                    @if($variant->image)
                                        <img src="{{ asset('uploads/products/' . $variant->image) }}"
                                            alt="{{ $variant->sku }}" title="Click to view item variant image assets">
                                    @else
                                        <div class="pv-var-img-ph"><i class="bi bi-box-seam"></i></div>
                                    @endif
                                </div>
                                <div class="pv-var-info">
                                    <div class="pv-var-row1">
                                        <div class="pv-var-sku">
                                            Expanded Technical Matrix
                                            <span class="pv-var-name-hint">Detailed breakdown for {{ $variant->sku }}</span>
                                        </div>
                                    </div>
                                    <div class="pv-var-stats">
                                        <div>
                                            <div class="pvs-lbl">Standard Price</div>
                                            <div class="pvs-val">₹{{ number_format($variant->offer_price ?? $variant->price, 2) }}</div>
                                        </div>
                                        <div>
                                            <div class="pvs-lbl text-success">Instant Price</div>
                                            <div class="pvs-val text-success">₹{{ number_format($variant->instant_offer_price ?? $variant->instant_price, 2) }}</div>
                                        </div>
                                        <div>
                                            <div class="pvs-lbl">Unit Qty</div>
                                            <div class="pvs-val">{{ $variant->qty }} {{ $variant->unit ?? '' }}</div>
                                        </div>
                                        <div>
                                            <div class="pvs-lbl text-warning">Royalty %</div>
                                            <div class="pvs-val text-warning">{{ $variant->royalty_percentage ?? 0 }}%</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if(($variant->attributes && count($variant->attributes) > 0) || ($variant->features && count($variant->features) > 0))
                                <div class="pv-var-meta-wrapper">
                                    @if($variant->attributes && count($variant->attributes) > 0)
                                        <div class="pv-meta-line">
                                            <span class="pv-meta-title-label">Attributes:</span>
                                            @foreach($variant->attributes as $keyName => $valString)
                                                <span class="pv-meta-pill">{{ $keyName }}: <strong>{{ $valString }}</strong></span>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if($variant->features && count($variant->features) > 0)
                                        <div class="pv-meta-line">
                                            <span class="pv-meta-title-label">Tech Specs:</span>
                                            @foreach($variant->features as $keyLabel => $valDetail)
                                                <span class="pv-meta-pill">{{ $keyLabel }}: <strong>{{ $valDetail }}</strong></span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endif

                            @if($variant->description && $variant->description !== $product->description)
                                <div class="pv-var-rich-body">
                                    <div style="font-size:10px; font-weight:800; text-transform:uppercase; color:var(--purple); margin-bottom:6px; letter-spacing:0.5px;">
                                        <i class="bi bi-file-earmark-text me-1"></i>Custom Variant Specification Override
                                    </div>
                                    <div class="pv-desc-full">{!! $variant->description !!}</div>
                                </div>
                            @endif

                            @if($variant->short_description || $variant->is_featured || $variant->is_trending)
                                <div class="pv-var-foot">
                                    @if($variant->short_description)
                                        <div class="pv-var-note">
                                            <i class="bi bi-info-circle" style="margin-top:1px;"></i>
                                            {{ $variant->short_description }}
                                        </div>
                                    @else
                                        <div></div>
                                    @endif
                                    <div class="pv-var-flags">
                                        @if($variant->is_featured) <span class="vf vf-feat"><i class="bi bi-star-fill me-1"></i>Featured</span> @endif
                                        @if($variant->is_trending) <span class="vf vf-trend"><i class="bi bi-lightning-fill me-1"></i>Trending</span> @endif
                                    </div>
                                </div>
                            @endif

                            @if($variant->gallery && count($variant->gallery) > 0 && $variant->gallery !== $product->gallery)
                                <div class="pv-vgal">
                                    <span class="pv-vgal-lbl">Variant Gallery</span>
                                    @foreach($variant->gallery as $vGalItem)
                                        <img src="{{ asset('uploads/products/gallery/' . $vGalItem) }}"
                                            alt="Variant Layer Asset" onclick="swapMain(this.src)"
                                            title="Click to pop-up into primary canvas">
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="pv-no-var">
                        <i class="bi bi-layers" style="font-size:32px;display:block;margin-bottom:10px;opacity:0.3;"></i>
                        No variants exist for this product.
                    </div>
                @endforelse

                <div class="pv-audit">
                    <i class="bi bi-clock-history"></i>
                    System Catalog Matrix Sync Log: {{ $product->updated_at->format('d M Y, h:i A') }}
                </div>
            </div>
        </div>
    </div>

    @include('admin.includes.footer_links')

    <script>
        const mainImg = document.getElementById('mainImage');
        const thumbs = document.querySelectorAll('.pv-thumb');
        mainImg.style.transition = 'opacity 0.18s ease';

        thumbs.forEach(t => {
            t.addEventListener('click', function () {
                swapMain(this.dataset.src);
                thumbs.forEach(x => x.classList.remove('active'));
                this.classList.add('active');
            });
        });

        function swapMain(src) {
            if (!src) return;
            mainImg.style.opacity = '0';
            setTimeout(() => {
                mainImg.src = src;
                mainImg.style.opacity = '1';
            }, 180);
        }

        function toggleVariant(headerElement) {
            const variantCard = headerElement.closest('.pv-variant');
            document.querySelectorAll('.pv-variant').forEach(card => {
                if (card !== variantCard) card.classList.remove('is-open');
            });
            variantCard.classList.toggle('is-open');
        }

        document.addEventListener('DOMContentLoaded', function () {
            const descBody = document.getElementById('richDescriptionBody');
            const toggleBtn = document.getElementById('readMoreToggle');

            if (!descBody) return;

            const contentNodes = Array.from(descBody.children);
            const cutoffIndex = 2;

            if (contentNodes.length > cutoffIndex) {
                contentNodes.forEach((node, index) => {
                    if (index >= cutoffIndex) {
                        node.classList.add('hidden-node');
                    }
                });

                toggleBtn.style.display = 'inline-flex';
                let isExpanded = false;

                toggleBtn.addEventListener('click', function () {
                    isExpanded = !isExpanded;

                    contentNodes.forEach((node, index) => {
                        if (index >= cutoffIndex) {
                            if (isExpanded) {
                                node.classList.remove('hidden-node');
                                node.classList.add('reveal-node');
                            } else {
                                node.classList.remove('reveal-node');
                                node.classList.add('hidden-node');
                            }
                        }
                    });

                    if (isExpanded) {
                        toggleBtn.innerHTML = 'Read Less <i class="bi bi-chevron-up"></i>';
                    } else {
                        toggleBtn.innerHTML = 'Read More <i class="bi bi-chevron-down"></i>';
                    }
                });
            }
        });
    </script>
</body>

</html>