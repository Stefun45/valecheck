@extends('layouts.legal', ['title' => 'Terms & Conditions'])

@section('content')
    <section>
        <p class="text-sm text-gray-600 leading-relaxed">
            These terms govern your use of ValeCheck, a website operated by <strong>Silverback Customs UK Ltd</strong>,
            trading as <strong>ValeCheck</strong> ("we", "us", "our"), registered office at Unit 2A, 35 Eastgate
            North, Driffield, YO25 6DG. By using this site or purchasing a report, you agree to these terms.
        </p>
    </section>

    <section>
        <h2 class="font-display font-bold text-lg text-vale-navy mb-2">1. The service</h2>
        <p class="text-sm text-gray-600 leading-relaxed">
            ValeCheck provides vehicle history and market valuation reports for a UK vehicle registration you enter.
            We offer two products: <strong>ValeCheck</strong> (history and provenance) and <strong>ValeCheck
            Plus</strong> (history, provenance and market valuation). Current prices are shown on the site and are
            inclusive of VAT.
        </p>
    </section>

    <section>
        <h2 class="font-display font-bold text-lg text-vale-navy mb-2">2. Data sources and accuracy</h2>
        <p class="text-sm text-gray-600 leading-relaxed">
            Report data is sourced from third-party providers, including <strong>data provided by Experian</strong>
            and vehicle data from the DVLA and DVSA, via One Auto API. We do not independently verify this data and
            make no guarantee that it is complete, current or accurate. Where a particular check (for example
            finance, write-off or stolen status) is not returned by a data provider, the report will say so clearly
            rather than presenting the absence of data as confirmation that the vehicle is clear.
        </p>
        <p class="text-sm text-gray-600 leading-relaxed mt-3">
            To the fullest extent permitted by law, we exclude liability for errors, omissions or inaccuracies in
            data supplied by Experian, the DVLA, the DVSA or any other third-party data provider. Where a data
            provider offers its own guarantee in respect of specific data (for example an Experian mileage
            guarantee), that guarantee is provided by and is the responsibility of that provider under its own
            terms, not by us.
        </p>
        <p class="text-sm text-gray-600 leading-relaxed mt-3">
            Market valuations, and any estimated repair costs, resale values or recommended purchase/bid amounts
            where shown, are guidance only, based on available data at the time of the report, and are not a
            guarantee of a vehicle's actual value or condition. You should not rely solely on a ValeCheck report
            when deciding whether to buy a vehicle — always inspect the vehicle in person or arrange an independent
            inspection, and verify key details (VIN, registration document, MOT, service history) directly.
        </p>
    </section>

    <section>
        <h2 class="font-display font-bold text-lg text-vale-navy mb-2">3. Payment</h2>
        <p class="text-sm text-gray-600 leading-relaxed">
            Payments are processed securely by Stripe. We do not store your card details. Prices are shown inclusive
            of VAT at the applicable rate.
        </p>
    </section>

    <section>
        <h2 class="font-display font-bold text-lg text-vale-navy mb-2">4. Delivery and your right to cancel</h2>
        <p class="text-sm text-gray-600 leading-relaxed">
            A report is digital content generated and made available to you immediately after payment is confirmed.
            Under the Consumer Contracts Regulations 2013, you would normally have a 14-day right to cancel a
            distance contract. By proceeding with payment, you expressly request that we begin generating your
            report immediately, and you acknowledge that once the report has been generated and made available to
            you, you lose the right to cancel.
        </p>
    </section>

    <section>
        <h2 class="font-display font-bold text-lg text-vale-navy mb-2">5. Refunds</h2>
        <p class="text-sm text-gray-600 leading-relaxed">
            If a report fails to generate due to a fault on our part, we will provide a full refund or, where
            applicable, restore any credit used. Refunds are not available simply because the report's findings were
            not what you expected — the report reflects the data available from our providers at the time of the
            check.
        </p>
    </section>

    <section>
        <h2 class="font-display font-bold text-lg text-vale-navy mb-2">6. Report availability</h2>
        <p class="text-sm text-gray-600 leading-relaxed">
            Completed reports, including any downloadable PDF, remain available in your account for a limited
            retention period shown on the report itself, after which the report content is permanently deleted. We
            recommend downloading a copy for your own records if you wish to keep it longer.
        </p>
    </section>

    <section>
        <h2 class="font-display font-bold text-lg text-vale-navy mb-2">7. Limitation of liability</h2>
        <p class="text-sm text-gray-600 leading-relaxed">
            To the fullest extent permitted by law, our liability to you in connection with a report is limited to
            the amount you paid for that report. We are not liable for any indirect or consequential loss, including
            losses arising from a decision to buy or not buy a vehicle based on a report. Nothing in these terms
            excludes or limits liability that cannot lawfully be excluded, including liability for death or personal
            injury caused by negligence, or for fraud.
        </p>
    </section>

    <section>
        <h2 class="font-display font-bold text-lg text-vale-navy mb-2">8. Your account</h2>
        <p class="text-sm text-gray-600 leading-relaxed">
            You are responsible for keeping your account details secure and for all activity under your account. You
            must provide accurate information when registering and purchasing reports.
        </p>
    </section>

    <section>
        <h2 class="font-display font-bold text-lg text-vale-navy mb-2">9. Changes to these terms</h2>
        <p class="text-sm text-gray-600 leading-relaxed">
            We may update these terms from time to time. The version in effect at the time you purchase a report is
            the version that applies to that purchase.
        </p>
    </section>

    <section>
        <h2 class="font-display font-bold text-lg text-vale-navy mb-2">10. Governing law</h2>
        <p class="text-sm text-gray-600 leading-relaxed">
            These terms are governed by the law of England and Wales, and the courts of England and Wales have
            exclusive jurisdiction over any dispute.
        </p>
    </section>

    <section>
        <h2 class="font-display font-bold text-lg text-vale-navy mb-2">11. Contact us</h2>
        <p class="text-sm text-gray-600 leading-relaxed">
            Silverback Customs UK Ltd, trading as ValeCheck<br>
            Unit 2A, 35 Eastgate North, Driffield, YO25 6DG
        </p>
    </section>
@endsection
