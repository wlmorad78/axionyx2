<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Competitor;
use App\Models\CompetitorBrand;
use App\Models\CompetitorNewProduct;
use App\Models\CompetitorPhoto;
use App\Models\CompetitorPriceSurvey;
use App\Models\CompetitorPriceSurveyItem;
use App\Models\CompetitorProduct;
use App\Models\CompetitorPromotion;
use App\Models\CompetitorPromotionItem;
use App\Models\Customer;
use App\Models\Item;
use App\Models\MarketIssue;
use App\Models\ShelfShareItem;
use App\Models\ShelfShareSurvey;
use App\Models\User;
use Illuminate\Database\Seeder;

class CompetitorFullSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            $items = Item::where('company_id', $company->id)->take(3)->get();
            $customers = Customer::where('company_id', $company->id)->take(2)->get();
            $adminUser = User::where('company_id', $company->id)->first();
            $employee = \App\Models\Employee::where('company_id', $company->id)->first();

            // Competitors
            $competitorsData = [
                ['competitor_code' => 'CMP-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-01', 'competitor_name' => 'شركة المنافس 1 - Competitor 1'],
                ['competitor_code' => 'CMP-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-02', 'competitor_name' => 'شركة المنافس 2 - Competitor 2'],
                ['competitor_code' => 'CMP-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-03', 'competitor_name' => 'شركة المنافس 3 - Competitor 3'],
            ];

            $compModels = [];
            foreach ($competitorsData as $c) {
                $compModels[] = Competitor::updateOrCreate(
                    ['company_id' => $company->id, 'competitor_code' => $c['competitor_code']],
                    ['competitor_name' => $c['competitor_name'], 'is_active' => true]
                );
            }

            // Competitor Brands
            foreach ($compModels as $i => $comp) {
                CompetitorBrand::updateOrCreate(
                    ['competitor_id' => $comp->id, 'brand_name' => 'علامة ' . ($i + 1) . ' - Brand ' . ($i + 1)],
                    ['is_active' => true]
                );
            }

            // Competitor Products
            foreach ($compModels as $i => $comp) {
                CompetitorProduct::updateOrCreate(
                    ['competitor_id' => $comp->id, 'product_name' => 'منتج المنافس ' . ($i + 1) . ' - Competitor Product ' . ($i + 1)],
                    ['is_active' => true]
                );
            }

            // Competitor Price Surveys
            if ($compModels && $items->isNotEmpty() && $employee) {
                $survey = CompetitorPriceSurvey::create([
                    'company_id' => $company->id,
                    'sales_rep_id' => $employee->id,
                    'customer_id' => $customers->first()?->id,
                    'survey_date' => now()->toDateString(),
                ]);

                $compProduct = CompetitorProduct::where('competitor_id', $compModels[0]->id)->first();
                if ($compProduct) {
                    CompetitorPriceSurveyItem::create([
                        'competitor_price_survey_id' => $survey->id,
                        'competitor_product_id' => $compProduct->id,
                        'price' => 25,
                        'stock_status' => 'AVAILABLE',
                    ]);
                }
            }

            // Competitor Promotions
            foreach ($compModels as $i => $comp) {
                $promo = CompetitorPromotion::create([
                    'competitor_id' => $comp->id,
                    'promotion_name' => 'عرض المنافس ' . ($i + 1) . ' - Competitor Promotion ' . ($i + 1),
                    'start_date' => now()->toDateString(),
                    'end_date' => now()->addMonth()->toDateString(),
                    'status' => 'ACTIVE',
                ]);

                $compProduct = CompetitorProduct::where('competitor_id', $comp->id)->first();
                if ($compProduct) {
                    CompetitorPromotionItem::create([
                        'competitor_promotion_id' => $promo->id,
                        'competitor_product_id' => $compProduct->id,
                        'offer_type' => 'DISCOUNT_PERCENT',
                        'offer_value' => 27,
                    ]);
                }
            }

            // Shelf Share Surveys
            if ($customers->isNotEmpty() && $employee) {
                $shelf = ShelfShareSurvey::create([
                    'company_id' => $company->id,
                    'customer_id' => $customers[0]->id,
                    'sales_rep_id' => $employee->id,
                    'survey_date' => now()->toDateString(),
                ]);

                ShelfShareItem::create([
                    'shelf_share_survey_id' => $shelf->id,
                    'brand_name' => 'مشروبات - Beverages',
                    'facings_count' => 7,
                    'shelf_percentage' => 35,
                ]);
            }

            // Competitor New Products
            if ($compModels && $employee) {
                $compProduct = CompetitorProduct::where('competitor_id', $compModels[0]->id)->first();
                if ($compProduct) {
                    CompetitorNewProduct::create([
                        'competitor_id' => $compModels[0]->id,
                        'competitor_product_id' => $compProduct->id,
                        'reported_by' => $employee->id,
                        'customer_id' => $customers->first()?->id,
                        'report_date' => now()->toDateString(),
                    ]);
                }
            }

            // Market Issues
            if ($employee) {
                MarketIssue::create([
                    'customer_id' => $customers->first()?->id,
                    'sales_rep_id' => $employee->id,
                    'issue_date' => now()->toDateString(),
                    'issue_type' => 'AVAILABILITY',
                    'description' => 'يوجد نقص في بعض المنتجات في المتاجر',
                    'priority' => 'HIGH',
                    'status' => 'OPEN',
                ]);
            }

            // Competitor Photos
            if ($compModels) {
                CompetitorPhoto::create([
                    'customer_id' => $customers->first()?->id,
                    'sales_rep_id' => $employee?->id,
                    'competitor_id' => $compModels[0]->id,
                    'photo_type' => 'SHELF',
                    'file_path' => 'competitors/sample-photo.jpg',
                ]);
            }
        }
    }
}
