<?php

/*
 * Homepage copy for the landing-page skill branch (elayadesign/ai-design-skills, landing-page-design).
 * English only on this branch: other languages fall back to these strings until the copy is approved and translated.
 * Figures in placeholders are rendered from the database in App\Livewire\Home, never typed here.
 */

return [
    'meta_title' => 'EU VAT calculator and current rates for 27 countries',
    'meta_description' => 'Add or remove VAT for any EU country at the current rate, with its date and source. Check VAT numbers in VIES. Free, no account.',

    'hero' => [
        'line_one' => 'Add or remove VAT',
        'line_two' => 'for any EU country',
        'subheadline' => 'Choose a country, enter an amount and read the net, the VAT and the total for your invoice, quote or receipt. Current rates for all 27 EU member states, plus Iceland, Norway, Switzerland, Turkey and the UK.',
        'calculator_label' => 'Calculate VAT on your amount',
        'free' => 'Free, no account',
    ],

    'calculator' => [
        'share' => 'Get a shareable breakdown',
    ],

    'problem' => [
        'heading' => 'A saved VAT rate goes out of date without telling you',
        'body' => 'The VAT history holds :count recorded rate changes. The latest took :country from :old to :new on :date. Here the calculator shows when its rate was last updated and links to its source, so you can see the rate is current before you use it.',
        'body_empty' => 'Rates change: a member state can raise or cut its VAT at the start of any month. Here the calculator shows when its rate was last updated and links to its source, so you can see the rate is current before you use it.',
        'link' => 'See all :count recorded changes',
        'link_empty' => 'See the VAT rate history',
    ],

    'rates' => [
        'map_link' => 'See every rate on the map',
    ],

    'benefits' => [
        'heading' => 'A figure you can check before you use it',
        'items' => [
            'preset' => [
                'title' => 'The current rate is already set',
                'detail' => 'Choose a country and its standard rate is selected for you. Its lower rates are one tap away, such as :rates in :country, and Custom % takes any rate from 0% to 100%.',
                'link' => 'Open the :country calculator',
            ],
            'cent' => [
                'title' => 'Add or remove VAT, to the cent',
                'detail' => ':net plus :country\'s :rate is :gross, and :gross including :rate leaves :net net. The calculator, the API and the MCP server round the same way, so the figures match wherever you get them.',
            ],
            'source' => [
                'title' => 'The date and the source beside the rate',
                'detail' => 'The calculator shows when its rate was last updated and links to where it comes from: the European Commission for the 27 member states, and the open VAT rates dataset for Iceland, Norway, Switzerland, Turkey and the UK.',
            ],
            'share' => [
                'title' => 'A result you can send',
                'detail' => 'Copy the result as one line, such as “:summary”, or open its own page with the full breakdown and the formula to send to a client or a colleague.',
            ],
            'amounts' => [
                'title' => 'Amounts the way you write them',
                'detail' => 'Type 1.234,56, 1,234.56, 1 234,5 or €249.90 and the calculator reads each one, or tap 100, 500, 1,000, 5,000 or 10,000.',
            ],
        ],
    ],

    'tagline' => [
        'The current rate is already filled in,',
        'dated and linked to its source.',
        'All you bring is the amount.',
    ],

    'steps' => [
        'heading' => 'From country to total in three steps',
        'items' => [
            ['title' => 'Choose the country', 'detail' => 'Search the :count countries by name. Its standard rate is selected for you, and the calculator remembers your choice on this device for 90 days.'],
            ['title' => 'Enter the amount', 'detail' => 'Pick Add VAT or Remove VAT, then type a figure or tap one of the common amounts. The net, the VAT and the total update as you type.'],
            ['title' => 'Copy or share the result', 'detail' => 'Copy the line for your invoice or email, or get a shareable breakdown with its formula on a page you can send.'],
        ],
        'start' => 'Start with the calculator',
    ],

    'proof' => [
        'heading' => 'Where every number comes from',
        'intro' => 'No testimonials or logos here, just sources you can check yourself.',
        'items' => [
            'commission' => ['title' => 'European Commission', 'detail' => 'Rates for the 27 member states. Their lower rates match the Taxes in Europe Database and the Your Europe table, and the table wins where the two disagree.'],
            'dataset' => ['title' => 'Open VAT rates dataset', 'detail' => 'Iceland, Norway, Switzerland, Turkey and the UK, plus the standard rate history. The site syncs it every Monday and checks it for consistency every day.'],
            'vies' => ['title' => 'VIES', 'detail' => 'VAT number checks in the European Commission\'s register. The site keeps a result for up to 7 days, and each result says where it came from.'],
            'maths' => ['title' => 'One set of maths', 'detail' => 'The calculator, the API and the MCP server round to the cent the same way, so the figures match.'],
            'code' => ['title' => 'Public code', 'detail' => 'The site\'s code is public on GitHub, so anyone can read how a figure is worked out.'],
            'languages' => ['title' => 'Every EU language', 'detail' => 'The site works in all 24 official languages of the European Union.'],
        ],
    ],

    'more' => [
        'heading' => 'When you need more than a figure',
        'validator' => [
            'title' => 'Check a VAT number',
            'detail' => 'Check a customer\'s number in VIES, the European Commission\'s register. The validator reads the country from the prefix and shows the company name and address where the member state shares them.',
            'link' => 'Check a VAT number in VIES',
        ],
        'compare' => [
            'title' => 'Compare countries',
            'detail' => 'Put two or three countries side by side, with the VAT each one charges on the same amount.',
            'link' => 'Compare any two or three countries',
        ],
        'developers' => [
            'title' => 'Use the data in your own code',
            'detail' => 'A REST API with no API key and an OpenAPI 3.1 description, every rate as CSV or JSON under CC BY 4.0, and an MCP server with six tools that only read data.',
            'link' => 'Read the API documentation',
        ],
    ],

    'faq' => [
        'heading' => 'Questions about EU VAT',
        'items' => [
            ['q' => 'Where do the VAT rates come from?', 'a' => 'Rates for the 27 member states come from European Commission data. Their lower rates match the Commission\'s Taxes in Europe Database and its Your Europe rates table, and the table wins where the two disagree. Rates for Iceland, Norway, Switzerland, Turkey and the UK, and the history of standard rates, come from the open VAT rates dataset on GitHub. The calculator links its rate to the source.'],
            ['q' => 'How often are the rates updated?', 'a' => 'The site syncs the rate dataset every Monday and checks the data for consistency every day. The calculator shows the date its rate was last updated, and the VAT history records each change with its date and its old and new rates.'],
            ['q' => 'How do I add VAT to a price?', 'a' => 'Multiply the net price by the rate divided by 100 to get the VAT, then add it to the net price. At :country\'s :rate, :net net carries :vat VAT, so the total is :gross. In the calculator, choose Add VAT, pick the country and type the net amount.'],
            ['q' => 'How do I take VAT out of a price that already includes it?', 'a' => 'Divide the price by 1 plus the rate divided by 100. At :country\'s :rate, :gross including VAT is :net net, so the VAT inside it is :vat. In the calculator, choose Remove VAT and type the total.'],
            ['q' => 'Can I use a reduced rate or a rate of my own?', 'a' => 'Yes. Every rate a country applies appears as an option beside its standard rate, such as :rates in :reduced_country. Choose Custom % to enter any rate from 0% to 100%.'],
            ['q' => 'Which EU country has the lowest and the highest VAT rate?', 'a' => ':lowest_country has the lowest standard rate at :lowest and :highest_country the highest at :highest. The average across the :count member states is :average, and EU rules require a standard rate of at least 15%.'],
            ['q' => 'Can I check a customer\'s VAT number here?', 'a' => 'Yes, for the 27 member states. The validator checks the number in VIES, the European Commission\'s register, reads the country from the prefix and shows the company name and address where the member state shares them. Greek numbers start with EL. The site keeps a result for up to 7 days, and each result says where it came from.'],
            ['q' => 'Can I get the rates and calculations in my own software?', 'a' => 'Yes. The REST API returns countries, rates and VAT calculations as JSON with no API key, has an OpenAPI 3.1 description and allows 120 requests a minute. You can also download every rate as CSV or JSON under CC BY 4.0, or connect Claude, ChatGPT, VS Code or Cursor to the MCP server and its six tools.'],
            ['q' => 'Is it free, and do I need an account?', 'a' => 'It is free and needs no account or email address. The calculator, the VAT number validator, the API, the dataset and the MCP server all work without signing up.'],
            ['q' => 'Is this tax advice?', 'a' => 'No. The rates are reference data. Which rate applies depends on the goods or services and on the customer, so confirm the treatment with your national tax administration or a tax adviser when it matters.'],
        ],
    ],

    'risk' => 'Free to use, with no account and no email address, so there is nothing to sign up for or cancel. The calculator shows the rate it used, when that rate was last updated and where it comes from, so you can check a figure before it goes on an invoice.',

    'final' => [
        'line' => 'The calculator at the top of this page has the current rate for :count countries ready.',
        'cta' => 'Calculate VAT on your amount',
    ],
];
