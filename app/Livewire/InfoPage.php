<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[Layout('layouts.app')]
class InfoPage extends Component
{
    public string $slug;

    public function mount(string $slug): void
    {
        if (! array_key_exists($slug, static::pages())) {
            throw new NotFoundHttpException();
        }

        $this->slug = $slug;
    }

    public function render(): View
    {
        return view('livewire.info-page', [
            'page' => static::pages()[$this->slug],
        ]);
    }

    /**
     * @return array<string, array{title: string, subtitle: string, icon: string, sections: array<int, array{heading: string, points: array<int, string>}>}>
     */
    private static function pages(): array
    {
        return [
            'safety-tips' => [
                'title' => 'Safety Tips',
                'subtitle' => 'Buying and selling second-hand items safely on Sajha Auction.',
                'icon' => 'o-shield-check',
                'sections' => [
                    [
                        'heading' => 'Before you meet',
                        'points' => [
                            'Only arrange meetups through the location shown on the listing or agreed in Messages — never share payment details outside the app.',
                            'Prefer public, well-lit places such as shopping complexes, offices, or busy streets for the handover.',
                            'Check the seller\'s or buyer\'s profile and past listings before committing to a deal.',
                        ],
                    ],
                    [
                        'heading' => 'At the handover',
                        'points' => [
                            'Inspect the item in person and confirm its condition matches the listing before you pay.',
                            'Bring a friend or family member along for high-value items when possible.',
                            'Avoid full advance payment for items you have not seen — pay on inspection whenever possible.',
                        ],
                    ],
                    [
                        'heading' => 'For auctions',
                        'points' => [
                            'Only bid what you are prepared to pay — bids on Sajha Auction are binding once placed.',
                            'Remember the current bid can legitimately go above the seller\'s original purchase price; that alone is not a red flag.',
                            'Report any seller who refuses to honor a completed auction to our support team.',
                        ],
                    ],
                    [
                        'heading' => 'Red flags to watch for',
                        'points' => [
                            'Requests to pay before meeting, or to pay to an external account outside the platform.',
                            'Listings with prices far below market value for the item\'s condition.',
                            'Sellers who avoid phone calls, refuse to meet, or pressure you to decide quickly.',
                        ],
                    ],
                ],
            ],
            'posting-rules' => [
                'title' => 'Posting Rules',
                'subtitle' => 'What every listing on Sajha Auction is expected to follow.',
                'icon' => 'o-clipboard-document-check',
                'sections' => [
                    [
                        'heading' => 'Accuracy',
                        'points' => [
                            'Use real photos of the actual item — not stock photos or images copied from other listings.',
                            'Describe the condition honestly (New, Like New, Used, or Poor) and disclose any defects, missing parts, or repairs.',
                            'List the correct category, and the original purchase price / purchase date if known, so buyers get an accurate estimated value.',
                        ],
                    ],
                    [
                        'heading' => 'Pricing & listings',
                        'points' => [
                            'Set a fair asking price or starting bid for the item\'s condition and age.',
                            'One listing per item — do not create duplicate listings for the same product.',
                            'Auction listings require admin approval; direct-sell listings are reviewed before going live.',
                        ],
                    ],
                    [
                        'heading' => 'Prohibited listings',
                        'points' => [
                            'No counterfeit, stolen, or illegal items.',
                            'No items that violate Nepal\'s import, export, or trade regulations.',
                            'No misleading titles, fake urgency ("last piece!"), or manipulated pricing.',
                        ],
                    ],
                    [
                        'heading' => 'Following through',
                        'points' => [
                            'Keep your meetup location and availability up to date.',
                            'Honor completed direct-sell orders and won auctions — repeated no-shows can lead to account restrictions.',
                            'Respond to buyer messages within a reasonable time.',
                        ],
                    ],
                ],
            ],
            'how-it-works' => [
                'title' => 'How It Works',
                'subtitle' => 'Buying, selling, and bidding on Sajha Auction in a few simple steps.',
                'icon' => 'o-light-bulb',
                'sections' => [
                    [
                        'heading' => '1. Create an account',
                        'points' => [
                            'Sign up as a buyer to browse and bid for free.',
                            'Apply to become a seller from "My Products" to list items for direct sale or auction.',
                        ],
                    ],
                    [
                        'heading' => '2. List or browse',
                        'points' => [
                            'Sellers upload photos, condition, price, and a meetup location, then submit for review.',
                            'Buyers search and filter by category, condition, and price on the Home and Search Products pages.',
                        ],
                    ],
                    [
                        'heading' => '3. Buy directly or bid live',
                        'points' => [
                            'Direct-sell items can be added to cart and checked out at the listed price.',
                            'Auction items are won by the highest bid when the countdown ends — watch the live auction room for real-time bidding.',
                        ],
                    ],
                    [
                        'heading' => '4. Meet up & complete the deal',
                        'points' => [
                            'Coordinate the handover through in-app Messages using the seller\'s listed meetup location.',
                            'Inspect the item, complete payment, and confirm the order or auction as delivered.',
                        ],
                    ],
                ],
            ],
            'help-center' => [
                'title' => 'Help Center',
                'subtitle' => 'Common questions, guides, and how to reach us.',
                'icon' => 'o-lifebuoy',
                'sections' => [
                    [
                        'heading' => 'Start here',
                        'points' => [
                            'Check the FAQ page for answers to the most common questions about bidding, selling, and orders.',
                            'New to the marketplace? Read the How It Works guide for a step-by-step overview.',
                            'Buying or selling for the first time? See the Buying Guide and Selling Guide for practical tips.',
                        ],
                    ],
                    [
                        'heading' => 'Account & orders',
                        'points' => [
                            'Manage your listings from "My Products", and track purchases from "My Orders".',
                            'Unread messages and bid updates appear under Notifications and Messages in the navbar.',
                        ],
                    ],
                    [
                        'heading' => 'Still need help?',
                        'points' => [
                            'Reach our support team any time — see Contact Us in the footer.',
                            'Found a bug or something not working as expected? Use Report a Bug in the footer to let us know.',
                        ],
                    ],
                ],
            ],
            'buying-guide' => [
                'title' => 'Buying Guide',
                'subtitle' => 'Getting the best deal, direct-sell or auction.',
                'icon' => 'o-shopping-bag',
                'sections' => [
                    [
                        'heading' => 'Before you buy',
                        'points' => [
                            'Compare the asking price against the listing\'s Estimated Value, when shown, to gauge if it\'s a fair deal.',
                            'Read the full description and check all photos for signs of wear the seller may not have mentioned.',
                            'Message the seller with any questions before committing.',
                        ],
                    ],
                    [
                        'heading' => 'Direct-sell purchases',
                        'points' => [
                            'Add the item to your cart and check out — you\'ll coordinate the meetup with the seller directly.',
                            'Negotiable-price listings are marked as such; you can message the seller to discuss the price.',
                        ],
                    ],
                    [
                        'heading' => 'Bidding in auctions',
                        'points' => [
                            'Set a maximum you\'re comfortable paying before you start bidding, and stick to it.',
                            'Watch the live countdown — auctions can end quickly once bidding heats up.',
                            'A winning bid is a commitment to buy; make sure you can complete the purchase before bidding.',
                        ],
                    ],
                ],
            ],
            'selling-guide' => [
                'title' => 'Selling Guide',
                'subtitle' => 'Listing items that sell faster, for a fair price.',
                'icon' => 'o-megaphone',
                'sections' => [
                    [
                        'heading' => 'Write a listing that converts',
                        'points' => [
                            'Take clear photos in good light from multiple angles, including any flaws.',
                            'Write an honest, specific description — brand, model, age, and reason for selling all build buyer trust.',
                            'Add the original purchase price and purchase date so the system can suggest a fair value and starting price.',
                        ],
                    ],
                    [
                        'heading' => 'Pricing it right',
                        'points' => [
                            'Use the Estimated Value shown while listing as a reference — you can still price above or below it.',
                            'For auctions, the suggested starting price is a reference only; you can set your own starting bid and reserve price.',
                        ],
                    ],
                    [
                        'heading' => 'Closing the sale',
                        'points' => [
                            'Respond to buyer messages promptly and keep your meetup location current.',
                            'Once a direct-sell order or auction is won, follow through — reliable sellers get repeat buyers.',
                        ],
                    ],
                ],
            ],
        ];
    }
}
