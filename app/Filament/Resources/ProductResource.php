<?php

namespace App\Filament\Resources;

use App\Enums\Enums\ProductStatusEnum;
use App\Enums\RolesEnum;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make()
                ->schema([
                    TextInput::make('title')
                        ->live(onBlur: true)
                        ->required()
                        ->afterStateUpdated(
                            function (string $operation, $state, callable $set) {
                                $set('slug', Str::slug($state));
                            }
                        ),
                    TextInput::make('slug')
                        ->required(),
                    Select::make('department_id')
                        ->relationship('department', 'name')
                        ->label(__('Department'))
                        ->preload()
                        ->searchable()
                        ->required()
                        ->reactive() // Make the field reactive to changes
                        ->afterStateUpdated(function (callable $set) {
                            $set('category_id', null); // Reset category when department changes
                        }),
                    Select::make('category_id')
                        ->relationship(
                            name: 'category',
                            titleAttribute: 'name',
                            modifyQueryUsing: function (Builder $query, callable $get) {
                                // Modify the category query based on the selected department
                                $departmentId = $get('department_id'); // Get selected department
                                if ($departmentId) {
                                    $query->where('department_id', $departmentId); // Filter categories based on department
                                }
                            }
                        )
                        ->label(__('Category'))
                        ->preload()
                        ->searchable()
                        ->required()
            ]),
        Forms\Components\RichEditor::make('description')
                ->required()
                ->toolbarButtons([
                    'blockquote',
                    'bold',
                    'bulletList',
                    'h2',
                    'h3',
                    'italic',
                    'link',
                    'orderedList',
                    'redo',
                    'strike',
                    'underline',
                    'undo',
                    'table',
                ])
                ->columnSpan(2),
        TextInput::make('price')
                ->required()
                ->numeric(),
        TextInput::make('quantity')
                ->integer(),
        Select::make('status')
                ->options(ProductStatusEnum::labels())
                ->default(ProductStatusEnum::Draft->value)
                ->required()
    ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();

        return $user && $user->hasRole(RolesEnum::Vendor);
    }
}