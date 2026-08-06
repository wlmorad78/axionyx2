<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Survey;
use App\Models\SurveyAssignment;
use App\Models\SurveyCategory;
use App\Models\SurveyQuestion;
use App\Models\SurveyQuestionOption;
use App\Models\SurveyQuestionRule;
use App\Models\SurveyResponse;
use App\Models\SurveyResponseAnswer;
use App\Models\SurveyResponseOption;
use App\Models\SurveyResponsePhoto;
use App\Models\SurveyScoringRule;
use App\Models\SurveyScore;
use App\Models\User;
use Illuminate\Database\Seeder;

class SurveyFullSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            $customers = Customer::where('company_id', $company->id)->take(2)->get();
            $adminUser = User::where('company_id', $company->id)->first();

            // Survey Categories
            $cat = SurveyCategory::updateOrCreate(
                ['company_id' => $company->id, 'code' => 'SC-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-01'],
                ['name' => 'رضا العملاء - Customer Satisfaction', 'description' => 'استبيانات قياس رضا العملاء', 'is_active' => true]
            );

            // Survey
            $survey = Survey::updateOrCreate(
                ['company_id' => $company->id, 'survey_code' => 'SRV-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-001'],
                [
                    'survey_category_id' => $cat->id,
                    'survey_name' => 'استبيان رضا العملاء - Customer Satisfaction Survey',
                    'description' => 'استبيان لقياس رضا العملاء عن الخدمات',
                    'start_date' => now()->toDateString(),
                    'end_date' => now()->addMonth()->toDateString(),
                    'status' => 'ACTIVE',
                    'created_by' => $adminUser?->id,
                ]
            );

            // Survey Questions
            $q1 = SurveyQuestion::create([
                'survey_id' => $survey->id,
                'question_no' => 1,
                'question_text' => 'ما مدى رضاك عن جودة المنتجات؟ - How satisfied are you with product quality?',
                'question_type' => 'RATING',
                'is_required' => true,
                'display_order' => 1,
            ]);

            $q2 = SurveyQuestion::create([
                'survey_id' => $survey->id,
                'question_no' => 2,
                'question_text' => 'ما هي أكثر ميزة تقدرها؟ - What feature do you value most?',
                'question_type' => 'SINGLE_CHOICE',
                'is_required' => true,
                'display_order' => 2,
            ]);

            // Survey Question Options
            SurveyQuestionOption::create([
                'survey_question_id' => $q2->id,
                'option_text' => 'الجودة - Quality',
                'option_value' => 'quality',
                'display_order' => 1,
            ]);

            SurveyQuestionOption::create([
                'survey_question_id' => $q2->id,
                'option_text' => 'السعر - Price',
                'option_value' => 'price',
                'display_order' => 2,
            ]);

            SurveyQuestionOption::create([
                'survey_question_id' => $q2->id,
                'option_text' => 'خدمة التوصيل - Delivery Service',
                'option_value' => 'delivery',
                'display_order' => 3,
            ]);

            // Survey Scoring Rules
            SurveyScoringRule::create([
                'survey_id' => $survey->id,
                'survey_question_id' => $q1->id,
                'expected_answer' => '5',
                'score' => 10,
            ]);

            SurveyScoringRule::create([
                'survey_id' => $survey->id,
                'survey_question_id' => $q2->id,
                'expected_answer' => 'quality',
                'score' => 10,
            ]);

            // Survey Responses
            if ($customers->isNotEmpty()) {
                $response = SurveyResponse::create([
                    'survey_id' => $survey->id,
                    'customer_id' => $customers[0]->id,
                    'response_date' => now()->toDateString(),
                    'notes' => 'استبيان مكتمل',
                ]);

                SurveyResponseAnswer::create([
                    'survey_response_id' => $response->id,
                    'survey_question_id' => $q1->id,
                    'answer_text' => '9',
                    'answer_numeric' => 9,
                ]);

                $option = SurveyQuestionOption::where('survey_question_id', $q2->id)->first();
                $answer2 = SurveyResponseAnswer::create([
                    'survey_response_id' => $response->id,
                    'survey_question_id' => $q2->id,
                    'answer_text' => 'الجودة - Quality',
                ]);

                if ($option) {
                    SurveyResponseOption::create([
                        'survey_response_answer_id' => $answer2->id,
                        'survey_question_option_id' => $option->id,
                    ]);
                }

                SurveyResponsePhoto::create([
                    'survey_response_id' => $response->id,
                    'survey_question_id' => $q1->id,
                    'file_path' => 'surveys/sample-photo.jpg',
                ]);

                SurveyScore::create([
                    'survey_response_id' => $response->id,
                    'total_score' => 85,
                    'max_score' => 100,
                    'achievement_percent' => 85,
                ]);

                // Survey Assignments
                SurveyAssignment::updateOrCreate(
                    ['survey_id' => $survey->id, 'customer_id' => $customers[0]->id],
                    ['assigned_date' => now()->toDateString(), 'status' => 'COMPLETED']
                );
            }
        }
    }
}
